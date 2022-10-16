<?php

namespace Expanse\QueueStats;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class QueueStatsEventProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        \Illuminate\Queue\Events\JobQueued::class => [
            Listeners\JobQueued::class
        ],
        \Illuminate\Queue\Events\JobProcessing::class => [
            Listeners\JobHandled::class
        ],
        \Illuminate\Queue\Events\JobProcessed::class => [
            Listeners\JobHandled::class
        ],
        \Illuminate\Queue\Events\JobFailed::class => [
            Listeners\JobFailed::class
        ],
    ];
}
