<?php

namespace Expanse\QueueStats;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

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

        $this->commands([
            Commands\QueueStatsCommand::class
        ]);

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command(Commands\QueueStatsCommand::class)->dailyAt('00:07');
        });

        $this->app->register(QueueStatsEventProvider::class);
    }
}
