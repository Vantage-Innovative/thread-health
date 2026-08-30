<?php

namespace Vantage\ThreadHealth\Recorders;

use Carbon\Carbon;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Config;
use Laravel\Pulse\Facades\Pulse;
use Throwable;

class ThreadTelemetryRecorder
{
    /** @var array<string, float> */
    private array $startedJobs = [];

    public array $listen = [JobProcessing::class, JobProcessed::class, JobFailed::class, QueryExecuted::class];

    public function register(callable $record, Application $app): void
    {
        $app->afterResolving(Kernel::class, fn (Kernel $kernel) => $kernel->whenRequestLifecycleIsLongerThan(-1, $record));
        $app->afterResolving(ExceptionHandler::class, fn (ExceptionHandler $handler) => $handler->reportable(fn (Throwable $exception) => $this->recordException($exception)));
    }

    public function record(Carbon|JobProcessing|JobProcessed|JobFailed|QueryExecuted $event, mixed ...$arguments): void
    {
        if ($event instanceof Carbon) {
            $this->recordRequest($event);

            return;
        }
        if ($event instanceof QueryExecuted) {
            if ($event->time >= $this->threshold('slow_query_ms')) {
                $this->count('technical.queries.slow');
            }

            return;
        }

        $uuid = $event->job->uuid();
        if ($event instanceof JobProcessing) {
            $this->startedJobs[$uuid] = microtime(true);

            return;
        }

        if ($event instanceof JobProcessed) {
            $this->count('technical.jobs.processed');
            $this->recordSlowJob($uuid);

            return;
        }

        $this->count('technical.jobs.failed');
        $this->recordSlowJob($uuid);
    }

    private function recordRequest(Carbon $startedAt): void
    {
        $this->count('technical.requests');
        if ($startedAt->diffInMilliseconds() >= $this->threshold('slow_request_ms')) {
            $this->count('technical.requests.slow');
        }
    }

    private function recordException(Throwable $exception): void
    {
        $this->count('technical.exceptions');
    }

    private function recordSlowJob(string $uuid): void
    {
        $startedAt = $this->startedJobs[$uuid] ?? null;
        unset($this->startedJobs[$uuid]);
        if ($startedAt !== null && ((microtime(true) - $startedAt) * 1000) >= $this->threshold('slow_job_ms')) {
            $this->count('technical.jobs.slow');
        }
    }

    private function count(string $type): void
    {
        Pulse::record($type, 'total')->count();
    }

    private function threshold(string $key): int
    {
        $value = Config::integer('thread-health.'.$key);
        if ($value < 0) {
            throw new \LogicException("Thread Health {$key} must not be negative.");
        }

        return $value;
    }
}
