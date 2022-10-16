<?php

namespace Expanse\QueueStats;

use Illuminate\Support\ServiceProvider;

class QueueStatsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../database/migrations/' => base_path('/database/migrations'),
        ], 'migrations');

        $this->app->register(QueueStatsEventProvider::class);
    }
}
