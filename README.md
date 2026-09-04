# Thread Health Reporter

`vantage/thread-health` records a small, owned set of aggregate technical-health measurements through Laravel Pulse and sends them to Thread.

Register the GitHub repository, install Laravel Pulse and this package, then publish the package configuration:

```bash
composer config repositories.vantage-thread-health vcs https://github.com/Vantage-Innovative/thread-health.git
composer require laravel/pulse vantage/thread-health:^0.1
php artisan vendor:publish --provider="Laravel\Pulse\PulseServiceProvider"
php artisan migrate
php artisan vendor:publish --tag=thread-health-config
```

Configure `THREAD_HEALTH_ENDPOINT`, `THREAD_HEALTH_TOKEN`, `THREAD_HEALTH_ENVIRONMENT`, and optionally the cadence/slow thresholds. The token is created in the application’s Thread project settings and is displayed once.

Telemetry is enabled by default only when `APP_ENV=production`. Local, test, and staging environments do not register the Pulse recorder, validate Pulse ingest, or expose the reporting command unless explicitly enabled. Set `THREAD_HEALTH_ENABLED=true` in a non-production environment only when it should report telemetry. Calling `ThreadHealth::report()` while disabled fails explicitly.

Schedule the reporter every fifteen minutes (or your configured cadence), but only when telemetry is enabled:

```php
if (config('thread-health.enabled')) {
    Schedule::command('thread-health:report')
        ->everyFifteenMinutes()
        ->withoutOverlapping()
        ->onOneServer();
}
```

You may invoke the reporter directly when needed:

```php
use Vantage\ThreadHealth\Facades\ThreadHealth;

ThreadHealth::report();
```

The reporter has no retry/outbox fallback. A failed HTTP request fails the command so missing telemetry remains visible in Thread.

## Metrics

- `technical.requests`
- `technical.exceptions`
- `technical.jobs.processed`
- `technical.jobs.failed`
- `technical.requests.slow`
- `technical.jobs.slow`
- `technical.queries.slow`

All metrics are unsampled counts. Slow thresholds default to 1,000 ms. The package uses Pulse’s public `Storage` aggregate contract and owns its own metric types; it does not read Pulse dashboard tables or built-in recorder shapes. It requires Pulse's `storage` ingest driver, which writes directly to Pulse's configured database storage; leave `PULSE_INGEST_DRIVER` unset (its default is `storage`) or set it to `storage`. Redis ingest is deliberately unsupported because delayed stream processing would make the reporting window unreliable.
