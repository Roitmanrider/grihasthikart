<?php

namespace Tests\Feature;

use App\Domains\Setting\Services\BusinessSettingService;
use App\Domains\Storefront\Services\StorefrontAccessService;
use App\Models\Customer;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

class SecurityOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);
        app(BusinessSettingService::class)->set('storefront.access_mode', StorefrontAccessService::PUBLIC_STOREFRONT);
        RateLimiter::clear('guest|127.0.0.1');
    }

    public function test_security_headers_are_applied_to_web_responses(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_admin_system_health_is_admin_only_and_does_not_render_secrets(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $settings = app(BusinessSettingService::class);
        $settings->set('payment.razorpay_key_id', 'rzp_test_public');
        $settings->set('payment.razorpay_key_secret', 'super-secret-key');
        $settings->set('payment.razorpay_webhook_secret', 'super-secret-webhook');

        $this->get(route('admin.system-health.index'))->assertRedirect(route('admin.login'));

        $this->actingAs($admin)
            ->get(route('admin.system-health.index'))
            ->assertOk()
            ->assertSee('System Health')
            ->assertSee('Razorpay Configured')
            ->assertDontSee('super-secret-key')
            ->assertDontSee('super-secret-webhook');
    }

    public function test_customer_login_and_autocomplete_are_rate_limited(): void
    {
        Customer::factory()->create(['mobile' => '9876543210']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('customer.login.request'), ['mobile' => '9876543210'])->assertRedirect();
        }

        $this->post(route('customer.login.request'), ['mobile' => '9876543210'])
            ->assertTooManyRequests();

        for ($i = 0; $i < 60; $i++) {
            $this->get(route('products.autocomplete', ['q' => 'rice']))->assertOk();
        }

        $this->get(route('products.autocomplete', ['q' => 'rice']))
            ->assertTooManyRequests();
    }

    public function test_scheduler_heartbeat_records_timestamp(): void
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
            ->assertDontSee('No heartbeat recorded yet');
    }

    public function test_media_service_rejects_executable_extensions_and_traversal_paths(): void
    {
        Storage::fake('uploads');
        $service = app(MediaService::class);

        $path = $service->store(UploadedFile::fake()->image('safe.jpg'), 'products/main');
        $this->assertStringStartsWith('uploads/products/main/', $path);

        $this->expectException(InvalidArgumentException::class);
        $service->store(UploadedFile::fake()->create('shell.php', 1, 'application/x-php'), 'products/main');
    }

    public function test_media_service_rejects_traversal_delete_paths(): void
    {
        Storage::fake('uploads');

        $this->expectException(InvalidArgumentException::class);
        app(MediaService::class)->delete('../.env');
    }
}
