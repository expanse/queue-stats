<?php

namespace Expanse\QueueStats\Listeners;

use Expanse\QueueStats\Models\QueueStats;

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
        QueueStats::create([
            'task' => get_class($event),
            'connection' => $event->connectionName,
            'class' => $event->job->class,
        ]);

        \Log::debug(json_encode($event));
    }
}
