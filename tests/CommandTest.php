<?php

namespace Expanse\QueueStats\Tests;

use Expanse\QueueStats\Models\QueueLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;

class CommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_parses_logs_correctly_for_unprovided_date() : void
    {
        $queuedLog = QueueLog::factory()->createOne([
            'task' => \Illuminate\Queue\Events\JobQueued::class,
            'class' => 'App\\Jobs\\TestClass',
            'job_id' => 1,
            'created_at' => Carbon::now()->subDay(),
        ]);

        $processingLog             = $queuedLog->replicate();
        $processingLog->task       = \Illuminate\Queue\Events\JobProcessing::class;
        $processingLog->created_at = $queuedLog->created_at->addSeconds(10);
        $processingLog->save();

        $processedLog             = $processingLog->replicate();
        $processedLog->task       = \Illuminate\Queue\Events\JobProcessed::class;
        $processedLog->created_at = $processingLog->created_at->addSeconds(10);
        $processedLog->save();

        Artisan::call('queue-stats');

        $this->assertDatabaseHas('queue_stats', [
            'class' => 'App\Jobs\TestClass',
            'queue_count' => 1,
            'fail_count' => 0,
            'processing_wait' => 10000.0,
            'processing_time' => 10000.0,
            'report_date' => Carbon::now()->subDay()->toDateString(),
        ]);
    }

    public function test_job_parses_logs_correctly_for_provided_date() : void
    {
        $queuedLog = QueueLog::factory()->createOne([
            'task' => \Illuminate\Queue\Events\JobQueued::class,
            'class' => 'App\\Jobs\\TestClass',
            'job_id' => 1,
            'created_at' => Carbon::now()->subDays(2),
        ]);

        $processingLog             = $queuedLog->replicate();
        $processingLog->task       = \Illuminate\Queue\Events\JobProcessing::class;
        $processingLog->created_at = $queuedLog->created_at->addSeconds(10);
        $processingLog->save();

        $processedLog             = $processingLog->replicate();
        $processedLog->task       = \Illuminate\Queue\Events\JobProcessed::class;
        $processedLog->created_at = $processingLog->created_at->addSeconds(10);
        $processedLog->save();

        Artisan::call('queue-stats', [ '--date' => Carbon::now()->subDays(2)->toDateString() ]);

        $this->assertDatabaseHas('queue_stats', [
            'class' => 'App\Jobs\TestClass',
            'queue_count' => 1,
            'fail_count' => 0,
            'processing_wait' => 10000.0,
            'processing_time' => 10000.0,
            'report_date' => Carbon::now()->subDays(2)->toDateString(),
        ]);
    }
}
