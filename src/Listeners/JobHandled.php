<?php

namespace Expanse\QueueStats\Listeners;

use Expanse\QueueStats\Models\QueueStats;

class JobHandled
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
        QueueStats::create([
            'task' => get_class($event),
            'connection' => $event->connectionName,
            'class' => $event->job->resolveName(),
        ]);
    }
}
