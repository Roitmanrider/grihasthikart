<?php

namespace Tests\Feature\Admin;

use App\Domains\Catalog\Contracts\BrandRepositoryInterface;
use App\Domains\Catalog\Services\BrandService;
use App\Models\Brand;
use App\Models\User;
use App\Services\MediaService;
use App\Services\SlugService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class BrandManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);

        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
    }

    public function test_admin_can_view_paginated_brands_with_search_filters(): void
    {
        Brand::factory()->create(['name' => 'Amul', 'slug' => 'amul', 'status' => true, 'is_featured' => true]);
        Brand::factory()->create(['name' => 'Inactive Brand', 'slug' => 'inactive-brand', 'status' => false]);

        $response = $this->actingAs($this->admin)->get('/admin/brands?search=Amul&status=1&is_featured=1');

        $response->assertOk();
        $response->assertSee('Amul');
        $response->assertDontSee('Inactive Brand');
    }

    public function test_admin_can_create_brand_with_generated_slug_seo_and_logo(): void
    {
        Storage::fake('uploads');

        $response = $this->actingAs($this->admin)->post('/admin/brands', [
            'name' => 'Tata Sampann',
            'description' => 'Staples and pantry essentials.',
            'logo' => UploadedFile::fake()->image('tata.jpg'),
            'website_url' => 'https://www.tatasampann.com',
            'meta_title' => 'Tata Sampann Products Online',
            'meta_description' => 'Buy Tata Sampann grocery products online.',
            'meta_keywords' => 'tata, sampann, grocery',
            'display_order' => 1,
            'status' => 1,
            'is_featured' => 1,
        ]);

        $response->assertRedirect(route('admin.brands.index'));

        $brand = Brand::query()->where('name', 'Tata Sampann')->firstOrFail();

        $this->assertSame('tata-sampann', $brand->slug);
        $this->assertSame('Tata Sampann Products Online', $brand->meta_title);
        $this->assertTrue($brand->is_featured);
        $this->assertNotNull($brand->logo);
        Storage::disk('uploads')->assertExists($brand->logo);
    }

    public function test_admin_can_update_brand(): void
    {
        $brand = Brand::factory()->create(['name' => 'Old Brand', 'slug' => 'old-brand']);

        $response = $this->actingAs($this->admin)->put('/admin/brands/'.$brand->id, [
            'name' => 'Amul',
            'slug' => 'amul',
            'description' => 'Dairy products.',
            'website_url' => 'https://amul.com',
            'display_order' => 2,
            'status' => 1,
            'is_featured' => 1,
        ]);

        $response->assertRedirect(route('admin.brands.index'));

        $brand->refresh();

        $this->assertSame('Amul', $brand->name);
        $this->assertSame('amul', $brand->slug);
        $this->assertTrue($brand->status);
        $this->assertTrue($brand->is_featured);
    }

    public function test_admin_can_soft_delete_and_restore_brand(): void
    {
        $brand = Brand::factory()->create();

        $this->actingAs($this->admin)->delete('/admin/brands/'.$brand->id)
            ->assertRedirect(route('admin.brands.index'));

        $this->assertSoftDeleted('brands', ['id' => $brand->id]);

        $this->actingAs($this->admin)->patch('/admin/brands/'.$brand->id.'/restore')
            ->assertRedirect(route('admin.brands.index', ['trashed' => 'with']));

        $this->assertNotSoftDeleted('brands', ['id' => $brand->id]);
    }

    public function test_admin_can_bulk_update_status_delete_and_restore(): void
    {
        $brands = Brand::factory()->count(2)->create(['status' => true]);
        $ids = $brands->pluck('id')->all();

        $this->actingAs($this->admin)->post('/admin/brands/bulk-action', [
            'ids' => $ids,
            'action' => 'deactivate',
        ])->assertRedirect(route('admin.brands.index'));

        $this->assertSame(0, Brand::query()->where('status', true)->count());

        $this->actingAs($this->admin)->post('/admin/brands/bulk-action', [
            'ids' => $ids,
            'action' => 'delete',
        ])->assertRedirect(route('admin.brands.index'));

        $this->assertSame(2, Brand::onlyTrashed()->count());

        $this->actingAs($this->admin)->post('/admin/brands/bulk-action', [
            'ids' => $ids,
            'action' => 'restore',
        ])->assertRedirect(route('admin.brands.index'));

        $this->assertSame(0, Brand::onlyTrashed()->count());
    }

    public function test_brand_validation_rejects_missing_name_invalid_url_and_invalid_logo(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/brands', [
            'name' => '',
            'website_url' => 'not-a-url',
            'logo' => UploadedFile::fake()->create('brand.pdf', 10, 'application/pdf'),
        ]);

        $response->assertSessionHasErrors(['name', 'website_url', 'logo']);
    }

    public function test_brand_routes_require_authentication_and_authorization_middleware(): void
    {
        foreach ([
            'admin.brands.index',
            'admin.brands.create',
            'admin.brands.store',
            'admin.brands.show',
            'admin.brands.edit',
            'admin.brands.update',
            'admin.brands.destroy',
            'admin.brands.restore',
            'admin.brands.bulk-action',
        ] as $routeName) {
            $middleware = Route::getRoutes()->getByName($routeName)->gatherMiddleware();

            $this->assertContains('auth', $middleware);
            $this->assertContains('can:manage-brands', $middleware);
        }
    }

    public function test_unauthorized_user_cannot_access_brand_administration(): void
    {
        $user = User::factory()->create(['email' => 'customer@example.com']);
        $brand = Brand::factory()->create();

        $this->actingAs($user)->get('/admin/brands')->assertForbidden();
        $this->actingAs($user)->get('/admin/brands/create')->assertForbidden();
        $this->actingAs($user)->post('/admin/brands', [
            'name' => 'Unauthorized Brand',
        ])->assertForbidden();
        $this->actingAs($user)->delete('/admin/brands/'.$brand->id)->assertForbidden();
    }

    public function test_default_admin_email_fallback_does_not_grant_brand_access(): void
    {
        config(['grihasthikart.admin_emails' => []]);

        $this->actingAs($this->admin)->get('/admin/brands')->assertForbidden();
    }

    public function test_configured_admin_email_receives_brand_access(): void
    {
        config(['grihasthikart.admin_emails' => ['brand-admin@example.com']]);

        $user = User::factory()->create(['email' => 'brand-admin@example.com']);

        $this->actingAs($user)->get('/admin/brands')->assertOk();
    }

    public function test_failed_brand_media_update_keeps_existing_media_and_removes_new_upload(): void
    {
        Storage::fake('uploads');

        $brand = Brand::factory()->create([
            'name' => 'Old Brand',
            'slug' => 'old-brand',
            'logo' => 'brands/logo/original.jpg',
        ]);

        Storage::disk('uploads')->put($brand->logo, 'old logo');

        $repository = Mockery::mock(BrandRepositoryInterface::class);
        $repository->shouldReceive('update')
            ->once()
            ->with($brand, Mockery::on(fn (array $data): bool => $data['name'] === 'Amul' && ! array_key_exists('logo', $data)))
            ->andReturn($brand);
        $repository->shouldReceive('update')
            ->once()
            ->with($brand, Mockery::on(fn (array $data): bool => array_key_exists('logo', $data)))
            ->andThrow(new RuntimeException('Database update failed.'));

        $service = new BrandService($repository, new SlugService, new MediaService);

        try {
            $service->update($brand, [
                'name' => 'Amul',
                'logo' => UploadedFile::fake()->image('new-logo.jpg'),
                'display_order' => 1,
                'status' => 1,
                'is_featured' => 1,
            ]);

            $this->fail('Expected the media database update to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Database update failed.', $exception->getMessage());
        }

        Storage::disk('uploads')->assertExists('brands/logo/original.jpg');
        $this->assertSame([], Storage::disk('uploads')->allFiles('uploads/brands/logo'));
        $this->assertSame('brands/logo/original.jpg', $brand->fresh()->logo);
    }
}
