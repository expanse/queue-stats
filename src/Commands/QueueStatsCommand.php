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

    protected $processedJobIds;

    protected $stats;

    public function handle() : int
    {
        $this->date = $this->option('date') ? CarbonImmutable::parse($this->option('date')) : CarbonImmutable::now()->subDay();
        $this->processedJobIds = collect();
        $followups  = collect();

        // Load up anything that's already been saved
        $this->stats = QueueStats::where('report_date', $this->date->toDateString())
            ->get();

        DB::transaction(function () use ($followups) {

            QueueLog::whereBetween('created_at', [ $this->date->startOfDay(), $this->date->endOfDay() ])
                ->orderBy('job_id', 'asc')
                ->get()
                ->groupBy('job_id')
                ->each(function ($job_group) use ($followups) {
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

                    $this->populateStatsForJob($job_group);
                });

            if ($followups->isNotEmpty()) {
                $progressBar = $this->output->createProgressBar($followups->count());
                $progressBar->setFormat($this->output->isVerbose() ? 'very_verbose' : 'normal');

                if ($this->output->isVerbose()) {
                    $this->info("Processing {$followups->count()} followup job(s)");
                }

                $progressBar->start();

                $followups->chunk(50)->each(function ($chunk) use ($progressBar) {
                    if ($this->output->isVeryVerbose()) {
                        $this->newLine();
                        $this->info("Fetching chunk of {$chunk->count()} job(s)");
                    }

                    QueueLog::whereIn('job_id', $chunk->all())
                        ->get()
                        ->groupBy('job_id')
                        ->each(function ($logGroup) use ($progressBar) {
                            if ($this->output->isVeryVerbose()) {
                                $this->newLine();
                                $this->info("Following up on {$logGroup->first()->job_id}");
                            }

                            $this->populateStatsForJob($logGroup);
                            $progressBar->advance();
                        });
                });

                $progressBar->finish();
                $this->newLine();

                if ($this->output->isVerbose()) {
                    $this->info("Completed processing {$followups->count()} followup job(s)");
                }
            }

            if ($this->stats->isNotEmpty()) {
                $statsProgressBar = $this->output->createProgressBar($this->stats->count());
                $statsProgressBar->setFormat($this->output->isVerbose() ? 'very_verbose' : 'normal');

                if ($this->output->isVerbose()) {
                    $this->info("Saving stats for {$this->stats->count()} job class(es)");
                }

                $statsProgressBar->start();

                $this->stats->each(function (QueueStats $queueStat) use ($statsProgressBar) {
                    if ($this->output->isVeryVerbose()) {
                        $this->newLine();
                        $this->info("Processing stats for {$queueStat->class}");
                    }

                    // Delete processed jobs for this class in chunks to avoid large IN clauses
                    $this->processedJobIds->chunk(500)->each(function ($chunk) use ($queueStat) {
                        QueueLog::whereBetween('created_at', [ $this->date->startOfDay(), $this->date->endOfDay() ])
                            ->where('created_at', '<=', CarbonImmutable::now()->toDateTimeString())
                            ->where('class', $queueStat->class)
                            ->whereIn('job_id', $chunk->all())
                            ->delete();
                    });

                    if ($queueStat->fail_count === null) {
                        $queueStat->fail_count = 0;
                    }
                    $queueStat->save();

                    $statsProgressBar->advance();
                });

                $statsProgressBar->finish();
                $this->newLine();

                if ($this->output->isVerbose()) {
                    $this->info("Completed saving stats for {$this->stats->count()} job class(es)");
                }
            }

        });

        return self::SUCCESS;
    }

    /**
     *
     * @param Collection<QueueLog> $job_group
     */
    private function populateStatsForJob($job_group) : void
    {
	    $queueStat = $this->stats->firstWhere('class', $job_group->first()->class);
		if ($queueStat === null) {
		    $queueStat = new QueueStats();
		}

	    $queueStat->class       = $job_group->first()->class;
	    $queueStat->report_date = $job_group->first()->created_at->toDateString();
	    $queueStat->queue_count = $queueStat->queue_count + 1;

		$jobQueued = $job_group->firstWhere('task', JobQueued::class);
		$jobProcessing = $job_group->firstWhere('task', JobProcessing::class);
		$jobProcessed = $job_group->firstWhere('task', JobProcessed::class);
		$jobFailed = $job_group->firstWhere('task', JobFailed::class);

		// If the job wasn't started, we don't need to track it
		if (! $jobProcessing) {
		    return;
		}

		// If the job didn't complete or fail, we don't need to track it
		if (! ($jobProcessed || $jobFailed)) {
		    return;
		}

	    if ($jobFailed !== null) {
	        $queueStat->fail_count = $queueStat->fail_count + 1;
	    }

		if ($jobQueued && $jobProcessing) {
    	    // At this point, we know that it was queued and started processing, so we
    	    // can determine the wait time for this task
    	    $queueStat->processing_wait = $queueStat->processing_wait +
    	        (
    				$jobQueued->created_at
    						->diffInRealMilliseconds($jobProcessing->created_at)
    			);
		}

	    // Now we determine how long the job took to execute, either through completion
	    // or through failure
	    $queueStat->processing_time = $queueStat->processing_time +
	        $jobProcessing->created_at
									->diffInRealMilliseconds(
    									($jobProcessed ?? $jobFailed)->created_at
             );

        if ($queueStat->processing_wait === null) {
            $queueStat->processing_wait = 0;
        }

	    $this->stats->put($job_group->first()->class, $queueStat);

	    // Track this job as successfully processed so we can clean up its logs
	    $this->processedJobIds->push($job_group->first()->job_id);
    }
}
