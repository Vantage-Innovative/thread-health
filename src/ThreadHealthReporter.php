<?php

namespace Vantage\ThreadHealth;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Client\Factory as HttpFactory;
use Laravel\Pulse\Contracts\Storage;

class ThreadHealthReporter
{
    /** @var list<string> */
    private const METRICS = [
        'technical.requests', 'technical.exceptions', 'technical.jobs.processed', 'technical.jobs.failed',
        'technical.requests.slow', 'technical.jobs.slow', 'technical.queries.slow',
    ];

    public function __construct(private Container $app, private HttpFactory $http) {}

    public function report(): int
    {
        if (! config('thread-health.enabled')) {
            throw new \LogicException('Thread Health is disabled. Set THREAD_HEALTH_ENABLED=true to report telemetry.');
        }

        $endpoint = $this->requiredString('endpoint');
        $token = $this->requiredString('token');
        $environment = $this->requiredString('environment');
        $minutes = config('thread-health.cadence_minutes');
        if (! is_int($minutes) || $minutes < 1) {
            throw new \LogicException('Thread Health cadence_minutes must be a positive integer.');
        }
        if (config('pulse.ingest.driver', 'database') !== 'database') {
            throw new \LogicException('Thread Health supports only Pulse direct database ingest; Redis ingest is not quantitative-safe.');
        }

        if (! $this->app->bound(Storage::class)) {
            throw new \LogicException('Thread Health requires Laravel Pulse and its Storage contract.');
        }

        /** @var Storage $storage */
        $storage = $this->app->make(Storage::class);

        $periodEnd = CarbonImmutable::now('UTC');
        $periodStart = $periodEnd->subMinutes($minutes);
        $interval = CarbonInterval::minutes($minutes);
        $metrics = [];
        foreach (self::METRICS as $metric) {
            $metrics[$metric] = (int) round((float) $storage->aggregateTotal($metric, 'count', $interval));
        }

        $this->http->acceptJson()
            ->withToken($token)
            ->connectTimeout((int) config('thread-health.connect_timeout_seconds'))
            ->timeout((int) config('thread-health.request_timeout_seconds'))
            ->post($endpoint, [
                'environment' => $environment,
                'period_start' => $periodStart->toISOString(),
                'period_end' => $periodEnd->toISOString(),
                'source' => 'pulse',
                'metadata' => [
                    'cadence_minutes' => $minutes,
                    'slow_request_ms' => config('thread-health.slow_request_ms'),
                    'slow_job_ms' => config('thread-health.slow_job_ms'),
                    'slow_query_ms' => config('thread-health.slow_query_ms'),
                ],
                'metrics' => $metrics,
            ])->throw();

        return count($metrics);
    }

    private function requiredString(string $key): string
    {
        $value = config('thread-health.'.$key);
        if (! is_string($value) || $value === '') {
            throw new \LogicException("Thread Health {$key} must be configured.");
        }

        return $value;
    }
}
