<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class RecordSchedulerHeartbeat extends Command
{
    protected $signature = 'ops:scheduler-heartbeat';

    protected $description = 'Record a lightweight timestamp proving the Laravel scheduler is running.';

    public function handle(): int
    {
        $timestamp = now(config('app.timezone'))->toISOString();

        Cache::forever('ops.scheduler_last_seen_at', $timestamp);
        File::ensureDirectoryExists(storage_path('framework'));
        File::put(storage_path('framework/scheduler-heartbeat.json'), json_encode([
            'last_seen_at' => $timestamp,
            'timezone' => config('app.timezone'),
            'command' => $this->signature,
        ], JSON_PRETTY_PRINT));

        $this->info('Scheduler heartbeat recorded.');

        return self::SUCCESS;
    }
}
