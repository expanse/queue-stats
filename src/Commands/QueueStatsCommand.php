<?php

namespace Expanse\QueueStats\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Expanse\QueueStats\Models\QueueLog;
use Expanse\QueueStats\Models\QueueStats;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobProcessed;

class QueueStatsCommand extends Command
{
    public $signature = 'queue-stats
                        {--date= : Run for a specific date; defaults to "yesterday"}
                        {--skip-cleanup : Prevents removal of data from the log table after processing}';

    public $description = 'Generate queue stats from the queue log';

    protected Carbon $date;

    public function handle(): int
    {
        $this->date = Carbon::now();
        $followups = collect();

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
                        $follups->push($job_group->first()->job_id);

                        return;
                    }

                    $queueStat = $stats->get($job_group->first()->class, new QueueStats());

                    $queueStat->class = $job_group->first()->class;
                    $queueStat->report_date = $job_group->first()->created_at->toDateString();
                    $queueStat->queue_count = $queueStat->queue_count + 1;

                    if ($job_group->contains('task', JobFailed::class)) {
                        $queueStat->fail_count = $queueStat->fail_count + 1;
                    }

                    // At this point, we know that it was queued and started processing, so we
                    // can determine the wait time for this task
                    $queueStat->processing_wait =
                        $queueStat->processing_wait +
                        ($job_group->firstWhere('task', JobProcessing::class)->created_at->diffInRealMilliseconds($job_group->firstWhere('task', JobQueued::class)->created_at));

                    // Now we determine how long the job took to execute, either through completion
                    // or through failure
                    $queueStat->processing_time =
                        $queueStat->processing_time +
                        ($job_group->firstWhere('task', JobProcessed::class) ?? $job_group->firstWhere('task', JobFailed::class))->created_at->diffInRealMilliseconds($job_group->firstWhere('task', JobProcessing::class)->created_at);

                    $stats->put($job_group->first()->class, $queueStat);
                });

            $stats->each(function (QueueStats $queueStat) {
                QueueLog::whereBetween('created_at', [ $this->date->startOfDay(), $this->date->endOfDay() ])
                    ->where('created_at', '<=', Carbon::now()->toDateTimeString())
                    ->where('class', $queueStat->class)
                    ->whereNotIn('job_id', $followups->all())
                    ->delete();


                $queueStat->save();
            });
        });

        return self::SUCCESS;
    }
}
