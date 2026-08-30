<?php

namespace Vantage\ThreadHealth;

use Illuminate\Support\ServiceProvider;
use Laravel\Pulse\Contracts\Storage;
use Laravel\Pulse\Facades\Pulse;
use Vantage\ThreadHealth\Commands\ReportTechnicalHealth;
use Vantage\ThreadHealth\Recorders\ThreadTelemetryRecorder;

class ThreadHealthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/thread-health.php', 'thread-health');
        $this->app->singleton(ThreadHealthReporter::class);
    }

    public function boot(): void
    {
        $this->publishes([__DIR__.'/../config/thread-health.php' => config_path('thread-health.php')], 'thread-health-config');

        if (! config('thread-health.enabled')) {
            return;
        }

        if (! $this->app->bound(Storage::class)) {
            throw new \LogicException('Thread Health requires Laravel Pulse and its Storage contract.');
        }
        if (config('pulse.ingest.driver', 'database') !== 'database') {
            throw new \LogicException('Thread Health supports only Pulse direct database ingest; Redis ingest is not quantitative-safe.');
        }

        Pulse::register([ThreadTelemetryRecorder::class => true]);
        $this->commands([ReportTechnicalHealth::class]);
    }
}
