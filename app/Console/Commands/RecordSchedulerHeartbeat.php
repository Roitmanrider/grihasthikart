<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RecordSchedulerHeartbeat extends Command
{
    protected $signature = 'ops:scheduler-heartbeat';

    protected $description = 'Record a lightweight timestamp proving the Laravel scheduler is running.';

    public function handle(): int
    {
        Cache::forever('ops.scheduler_last_seen_at', now(config('app.timezone'))->toISOString());

        $this->info('Scheduler heartbeat recorded.');

        return self::SUCCESS;
    }
}
