<?php

namespace Expanse\QueueStats\Listeners;

use Expanse\QueueStats\Models\QueueLog;

class JobFailed
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
            'queue' => $event->job->getQueue(),
            'class' => $event->job->resolveName(),
            'job_id' => $event->job->getJobId(),
        ]);
    }
}
