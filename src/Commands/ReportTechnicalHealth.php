<?php

namespace Vantage\ThreadHealth\Commands;

use Illuminate\Console\Command;
use Throwable;
use Vantage\ThreadHealth\ThreadHealthReporter;

class ReportTechnicalHealth extends Command
{
    protected $signature = 'thread-health:report';

    protected $description = 'Report the latest Pulse-backed technical health aggregate to Thread';

    public function handle(ThreadHealthReporter $reporter): int
    {
        try {
            $count = $reporter->report();
            $this->info("Reported {$count} technical-health metrics to Thread.");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
