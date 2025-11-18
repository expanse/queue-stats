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

        if ($this->output->isVerbose()) {
            $this->info("Processing queue logs for {$this->date->toDateString()}");
        }

        // Process logs in chunks to avoid loading everything into memory
        $jobIdBuffer = collect();

        QueueLog::whereBetween('created_at', [ $this->date->startOfDay(), $this->date->endOfDay() ])
            ->orderBy('job_id', 'asc')
            ->chunkById(50, function ($logs) use ($followups, &$jobIdBuffer) {
                if ($this->output->isVeryVerbose()) {
                    $this->info("Processing chunk of {$logs->count()} log entries");
                }

                // Buffer job IDs to process in batches of 50 unique job IDs
                $jobIdBuffer = $jobIdBuffer->merge($logs->pluck('job_id')->unique());

                // When we have 50 or more unique job IDs, process them
                if ($jobIdBuffer->count() >= 50) {
                    $this->processJobBatch($jobIdBuffer, $followups);
                    $jobIdBuffer = collect();
                }
            });

        // Process any remaining job IDs in the buffer
        if ($jobIdBuffer->isNotEmpty()) {
            $this->processJobBatch($jobIdBuffer, $followups);
        }

        // Process followups (jobs that didn't have complete event data in the date range)
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

                // Save stats and cleanup after each chunk
                DB::transaction(function () {
                    $this->saveStats();

                    if (! $this->option('skip-cleanup')) {
                        $this->cleanupProcessedLogs();
                        // Clear the buffer after cleanup
                        $this->processedJobIds = collect();
                    }
                });
            });

            $progressBar->finish();
            $this->newLine();

            if ($this->output->isVerbose()) {
                $this->info("Completed processing {$followups->count()} followup job(s)");
            }
        }

        // Final save and cleanup
        DB::transaction(function () {
            $this->saveStats();

            if (! $this->option('skip-cleanup')) {
                $this->cleanupProcessedLogs();
            }
        });

        return self::SUCCESS;
    }

    /**
     * Process a batch of job IDs
     */
    private function processJobBatch($jobIds, $followups) : void
    {
        if ($this->output->isVeryVerbose()) {
            $this->info("Processing batch of {$jobIds->count()} unique job IDs");
        }

        QueueLog::whereIn('job_id', $jobIds->all())
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

        // Save stats and cleanup after processing each batch
        DB::transaction(function () {
            $this->saveStats();

            if (! $this->option('skip-cleanup')) {
                $this->cleanupProcessedLogs();
                // Clear the buffer after cleanup
                $this->processedJobIds = collect();
            }
        });
    }

    /**
     * Save accumulated stats to the database
     */
    private function saveStats() : void
    {
        if ($this->stats->isEmpty()) {
            return;
        }

        if ($this->output->isVeryVerbose()) {
            $this->info("Saving stats for {$this->stats->count()} job class(es)");
        }

        $this->stats->each(function (QueueStats $queueStat) {
            if ($queueStat->fail_count === null) {
                $queueStat->fail_count = 0;
            }
            $queueStat->save();
        });
    }

    /**
     * Clean up processed logs from the queue_log table
     */
    private function cleanupProcessedLogs() : void
    {
        if ($this->processedJobIds->isEmpty()) {
            return;
        }

        if ($this->output->isVerbose()) {
            $this->info("Cleaning up {$this->processedJobIds->count()} processed log entries");
        }

        // Delete processed jobs in chunks to avoid large IN clauses
        $this->processedJobIds->chunk(500)->each(function ($chunk) {
            QueueLog::whereBetween('created_at', [ $this->date->startOfDay(), $this->date->endOfDay() ])
                ->where('created_at', '<=', CarbonImmutable::now()->toDateTimeString())
                ->whereIn('job_id', $chunk->all())
                ->delete();
        });

        if ($this->output->isVerbose()) {
            $this->info("Cleanup completed");
        }
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
