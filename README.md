# Thread Health Reporter

`vantage/thread-health` records a small, owned set of aggregate technical-health measurements through Laravel Pulse and sends them to Thread.

Install Laravel Pulse and this package, then publish the package configuration:

```bash
php artisan vendor:publish --tag=thread-health-config
```

Configure `THREAD_HEALTH_ENDPOINT`, `THREAD_HEALTH_TOKEN`, `THREAD_HEALTH_ENVIRONMENT`, and optionally the cadence/slow thresholds. The token is created in the application’s Thread project settings and is displayed once.

Schedule the reporter every fifteen minutes (or your configured cadence):

```php
Schedule::command('thread-health:report')->everyFifteenMinutes()->withoutOverlapping()->onOneServer();
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

All metrics are unsampled counts. Slow thresholds default to 1,000 ms. The package uses Pulse’s public `Storage` aggregate contract and owns its own metric types; it does not read Pulse dashboard tables or built-in recorder shapes. Redis ingest is deliberately unsupported because delayed stream processing would make the reporting window unreliable.
