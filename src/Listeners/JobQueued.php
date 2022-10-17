<?php

namespace Expanse\QueueStats\Listeners;

use Expanse\QueueStats\Models\QueueLog;

class JobQueued
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle($event)
    {
        QueueLog::create([
            'task' => get_class($event),
            'connection' => $event->connectionName,
            // @TODO Laravel doesn't actually provide this information
            // in the same way to JobQueued that it does to JobProcessing
            // or JobProcessed or JobFailed
            'queue' => $event->job->queue ?? '',
            'class' => $event->job->class ?? get_class($event->job),
            'job_id' => $event->id,
        ]);
    }
}
