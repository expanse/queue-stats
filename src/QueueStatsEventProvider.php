<?php

namespace Expanse\QueueStats;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class QueueStatsEventProvider extends ServiceProvider
{

	public function boot() : void
	{
  Event::listen(
        \Illuminate\Queue\Events\JobQueued::class, Listeners\JobQueued::class
    );
    Event::listen(
        \Illuminate\Queue\Events\JobProcessing::class, Listeners\JobHandled::class
    );
    Event::listen(
        \Illuminate\Queue\Events\JobProcessed::class, Listeners\JobHandled::class
    );

    Event::listen(
        \Illuminate\Queue\Events\JobFailed::class, Listeners\JobFailed::class
    );
	}
}
