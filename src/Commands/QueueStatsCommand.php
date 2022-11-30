<?php

namespace Expanse\QueueStats\Commands;

use Carbon\CarbonImmutable;
use Expanse\QueueStats\Models\QueueLog;
use Expanse\QueueStats\Models\QueueStats;
use Illuminate\Console\Command;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Support\Facades\DB;

class QueueStatsCommand extends Command
{
    public $signature = 'queue-stats
                        {--date= : Run for a specific date; defaults to "yesterday"}
                        {--skip-cleanup : Prevents removal of data from the log table after processing}';

    public $description = 'Generate queue stats from the queue log';

    protected CarbonImmutable $date;

    public function handle() : int
    {
        $this->date = $this->option('date') ? CarbonImmutable::parse($this->option('date')) : CarbonImmutable::now()->subDay();
        $followups  = collect();

        DB::transaction(function () use ($followups) {
            // Load up anything that's already been saved
            $stats = QueueStats::where('report_date', $this->date->toDateString())
                ->get();

            QueueLog::whereBetween('created_at', [ $this->date->startOfDay(), $this->date->endOfDay() ])
                ->get()
                ->groupBy('job_id')
                ->each(function ($job_group) use ($stats, $followups) {
                    // If the job_group doesn't have (JobQueued *and* one of JobFailed, JobProcessed)
                    // then we add it to the followups collection to query manually
                    if (! (
                        $job_group->contains('task', JobQueued::class)
                        && (
                            $job_group->contains('task', JobFailed::class)
                            || $job_group->contains('task', JobProcessed::class)
                        )
                    )) {
                        $followups->push($job_group->first()->job_id);

                        return;
                    }

                    $queueStat = $stats->get($job_group->first()->class, new QueueStats());

                    $queueStat->class       = $job_group->first()->class;
                    $queueStat->report_date = $job_group->first()->created_at->toDateString();
                    $queueStat->queue_count = $queueStat->queue_count + 1;

                    if ($job_group->contains('task', JobFailed::class)) {
                        $queueStat->fail_count = $queueStat->fail_count + 1;
                    }

                    // At this point, we know that it was queued and started processing, so we
                    // can determine the wait time for this task
                    $queueStat->processing_wait = $queueStat->processing_wait +
                        ($job_group->firstWhere('task', JobProcessing::class)->created_at->diffInRealMilliseconds($job_group->firstWhere('task', JobQueued::class)->created_at));

                    // Now we determine how long the job took to execute, either through completion
                    // or through failure
                    $queueStat->processing_time = $queueStat->processing_time +
                        ($job_group->firstWhere('task', JobProcessed::class) ?? $job_group->firstWhere('task', JobFailed::class))->created_at->diffInRealMilliseconds($job_group->firstWhere('task', JobProcessing::class)->created_at);

                    $stats->put($job_group->first()->class, $queueStat);
                });

            $stats->each(function (QueueStats $queueStat) use ($followups) {
                QueueLog::whereBetween('created_at', [ $this->date->startOfDay(), $this->date->endOfDay() ])
                    ->where('created_at', '<=', CarbonImmutable::now()->toDateTimeString())
                    ->where('class', $queueStat->class)
                    ->whereNotIn('job_id', $followups->all())
                    ->delete();

                if ($queueStat->fail_count === null) {
                    $queueStat->fail_count = 0;
                }
                $queueStat->save();
            });
        });

        return self::SUCCESS;
    }
}
