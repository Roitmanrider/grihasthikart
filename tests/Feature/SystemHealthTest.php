<?php

namespace Tests\Feature;

use App\Domains\Setting\Services\BusinessSettingService;
use App\Domains\Storefront\Services\StorefrontAccessService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SystemHealthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);
        app(BusinessSettingService::class)->set('storefront.access_mode', StorefrontAccessService::PUBLIC_STOREFRONT);
    }

    public function test_system_health_reads_persistent_scheduler_heartbeat_when_cache_is_empty(): void
    {
        Cache::forget('ops.scheduler_last_seen_at');
        File::delete(storage_path('framework/scheduler-heartbeat.json'));

        Artisan::call('ops:scheduler-heartbeat');
        $this->assertNotEmpty(Cache::get('ops.scheduler_last_seen_at'));
        $this->assertFileExists(storage_path('framework/scheduler-heartbeat.json'));

        Cache::forget('ops.scheduler_last_seen_at');

        $this->actingAs(User::factory()->create(['email' => 'admin@example.com']))
            ->get(route('admin.system-health.index'))
            ->assertOk()
            ->assertSee('Scheduler Last Seen')
            ->assertDontSee('No heartbeat recorded yet');
    }
}
