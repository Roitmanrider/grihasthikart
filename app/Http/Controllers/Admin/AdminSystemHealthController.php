<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Setting\Services\BusinessSettingService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AdminSystemHealthController extends Controller
{
    public function __construct(private readonly BusinessSettingService $settings) {}

    public function __invoke()
    {
        $db = $this->databaseStatus();
        $uploadPath = config('filesystems.disks.uploads.root');
        $schedulerLastSeen = $this->schedulerLastSeen();

        return view('admin.system-health.index', [
            'checks' => [
                ['label' => 'Application Environment', 'value' => app()->environment(), 'ok' => app()->environment('production')],
                ['label' => 'Debug Mode', 'value' => config('app.debug') ? 'On' : 'Off', 'ok' => ! config('app.debug')],
                ['label' => 'PHP Version', 'value' => PHP_VERSION, 'ok' => version_compare(PHP_VERSION, '8.4.0', '>=')],
                ['label' => 'Database Connection', 'value' => $db['message'], 'ok' => $db['ok']],
                ['label' => 'Required Tables', 'value' => $this->requiredTablesStatus(), 'ok' => $this->requiredTablesPresent()],
                ['label' => 'Storage Writable', 'value' => storage_path(), 'ok' => File::isWritable(storage_path())],
                ['label' => 'Cache Writable', 'value' => storage_path('framework/cache'), 'ok' => File::isWritable(storage_path('framework/cache'))],
                ['label' => 'Uploads Writable', 'value' => $uploadPath, 'ok' => is_string($uploadPath) && File::isWritable($uploadPath)],
                ['label' => 'Scheduler Last Seen', 'value' => $schedulerLastSeen ?: 'No heartbeat recorded yet', 'ok' => $this->schedulerFresh($schedulerLastSeen)],
                ['label' => 'Razorpay Configured', 'value' => $this->settings->razorpayConfigured() ? 'Configured' : 'Missing key/secret', 'ok' => $this->settings->razorpayConfigured()],
                ['label' => 'Razorpay Mode', 'value' => str((string) $this->settings->get('payment.razorpay_mode', 'test'))->headline(), 'ok' => true],
                ['label' => 'Razorpay Webhook Secret', 'value' => filled($this->settings->get('payment.razorpay_webhook_secret')) ? 'Configured' : 'Missing', 'ok' => filled($this->settings->get('payment.razorpay_webhook_secret'))],
                ['label' => 'Queue Driver', 'value' => config('queue.default'), 'ok' => true],
                ['label' => 'Session Driver', 'value' => config('session.driver'), 'ok' => config('session.driver') === 'database'],
            ],
        ]);
    }

    private function databaseStatus(): array
    {
        try {
            $version = DB::selectOne('select version() as version')?->version ?? 'connected';

            return ['ok' => true, 'message' => 'Connected (MySQL '.$version.')'];
        } catch (Throwable $exception) {
            return ['ok' => false, 'message' => 'Connection failed'];
        }
    }

    private function requiredTablesStatus(): string
    {
        return $this->requiredTablesPresent() ? 'Present' : 'Missing required table(s)';
    }

    private function requiredTablesPresent(): bool
    {
        foreach (['users', 'customers', 'products', 'product_variants', 'inventories', 'orders', 'payments', 'sessions'] as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    private function schedulerFresh(?string $timestamp): bool
    {
        if (! $timestamp) {
            return false;
        }

        return now(config('app.timezone'))->diffInMinutes($timestamp) <= 10;
    }

    private function schedulerLastSeen(): ?string
    {
        $cached = Cache::get('ops.scheduler_last_seen_at');

        if ($cached) {
            return (string) $cached;
        }

        $path = storage_path('framework/scheduler-heartbeat.json');

        if (! File::exists($path)) {
            return null;
        }

        $payload = json_decode((string) File::get($path), true);

        return is_array($payload) && isset($payload['last_seen_at'])
            ? (string) $payload['last_seen_at']
            : null;
    }
}
