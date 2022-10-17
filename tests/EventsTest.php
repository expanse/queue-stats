<?php

namespace Expanse\QueueStats\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;

class EventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_queued_logs_event() : void
    {
        event(new \Illuminate\Queue\Events\JobQueued($connectionName = 'default', $id = 1, $job = new class () {
            public $class = 'App\Listeners\QueuedListener';
            public $queue = 'default';
        }));

        $this->assertDatabaseHas('queue_log', [
            'task' => \Illuminate\Queue\Events\JobQueued::class,
            'connection' => $connectionName,
            'job_id' => $id,
            'class' => 'App\Listeners\QueuedListener',
        ]);
    }

    /**
     * @dataProvider eventProvider
     */
    public function test_events_create_logs($eventClass) : void
    {
        event(new $eventClass(
            $connectionName = 'default',
            new class () {
                public $class = 'App\Listeners\QueuedListener';
                public $queue = 'default';

                public function getQueue() {
                    return 'default';
                }

                public function resolveName() {
                    return $this->class;
                }

                public function getJobId() {
                    return 1;
                }
            }
        ));

        $this->assertDatabaseHas('queue_log', [
            'task' => $eventClass,
            'connection' => $connectionName,
            'job_id' => 1,
            'class' => 'App\Listeners\QueuedListener',
        ]);
    }

    public function test_job_failed_logs_event() : void
    {
        event(new \Illuminate\Queue\Events\JobFailed(
            $connectionName = 'default',
            new class () {
                public $class = 'App\Listeners\QueuedListener';
                public $queue = 'default';

                public function getQueue() {
                    return 'default';
                }

                public function resolveName() {
                    return $this->class;
                }

                public function getJobId() {
                    return 1;
                }
            },
            new \Exception("Test")
        ));

        $this->assertDatabaseHas('queue_log', [
            'task' => \Illuminate\Queue\Events\JobFailed::class,
            'connection' => $connectionName,
            'job_id' => 1,
            'class' => 'App\Listeners\QueuedListener',
        ]);
    }
    public function eventProvider() : array
    {
        return [
            [
                \Illuminate\Queue\Events\JobProcessing::class,
            ],
            [
                \Illuminate\Queue\Events\JobProcessed::class,
            ],
        ];
    }
}
