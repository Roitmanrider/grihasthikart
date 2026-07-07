<?php

namespace Tests\Feature\Admin;

use App\Domains\Catalog\Contracts\ProductImageRepositoryInterface;
use App\Domains\Catalog\Services\ProductImageService;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ProductImageManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);

        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
    }

    public function test_admin_can_upload_product_image(): void
    {
        Storage::fake('uploads');
        $product = Product::factory()->create();

        $response = $this->actingAs($this->admin)->post(route('admin.products.images.store', $product), [
            'images' => [UploadedFile::fake()->image('front.jpg')],
            'alt_text' => 'Wheat atta front pack',
            'title' => 'Front Pack',
            'is_primary' => 1,
            'status' => 1,
        ]);

        $response->assertRedirect();

        $image = ProductImage::query()->where('product_id', $product->id)->firstOrFail();

        $this->assertNull($image->product_variant_id);
        $this->assertTrue($image->is_primary);
        $this->assertSame('Front Pack', $image->title);
        Storage::disk('uploads')->assertExists($image->path);
    }

    public function test_admin_can_upload_variant_image(): void
    {
        Storage::fake('uploads');
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        $this->actingAs($this->admin)->post(route('admin.products.variants.images.store', [$product, $variant]), [
            'images' => [UploadedFile::fake()->image('variant.jpg')],
            'title' => 'Variant Pack',
            'is_primary' => 1,
            'status' => 1,
        ])->assertRedirect();

        $image = ProductImage::query()->where('product_variant_id', $variant->id)->firstOrFail();

        $this->assertSame($product->id, $image->product_id);
        $this->assertTrue($image->is_primary);
        Storage::disk('uploads')->assertExists($image->path);
    }

    public function test_variant_image_rejects_variant_from_another_product(): void
    {
        Storage::fake('uploads');
        $product = Product::factory()->create();
        $otherProduct = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $otherProduct->id]);

        $this->actingAs($this->admin)
            ->from(route('admin.products.edit', $product))
            ->post(route('admin.products.variants.images.store', [$product, $variant]), [
                'images' => [UploadedFile::fake()->image('variant.jpg')],
            ])
            ->assertRedirect(route('admin.products.edit', $product))
            ->assertSessionHasErrors('image');

        $this->assertSame([], Storage::disk('uploads')->allFiles());
    }

    public function test_primary_product_image_clears_previous_primary(): void
    {
        $product = Product::factory()->create();
        $oldPrimary = ProductImage::factory()->primary()->create(['product_id' => $product->id]);
        $newPrimary = ProductImage::factory()->create(['product_id' => $product->id]);

        $this->actingAs($this->admin)
            ->patch(route('admin.products.images.primary', [$product, $newPrimary]))
            ->assertRedirect();

        $this->assertFalse($oldPrimary->fresh()->is_primary);
        $this->assertTrue($newPrimary->fresh()->is_primary);
    }

    public function test_primary_variant_image_clears_previous_primary(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        $oldPrimary = ProductImage::factory()->primary()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
        ]);
        $newPrimary = ProductImage::factory()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
        ]);

        $this->actingAs($this->admin)
            ->patch(route('admin.products.variants.images.primary', [$product, $variant, $newPrimary]))
            ->assertRedirect();

        $this->assertFalse($oldPrimary->fresh()->is_primary);
        $this->assertTrue($newPrimary->fresh()->is_primary);
    }

    public function test_primary_image_delete_is_blocked_and_non_primary_can_restore(): void
    {
        $product = Product::factory()->create();
        $primary = ProductImage::factory()->primary()->create(['product_id' => $product->id]);
        $image = ProductImage::factory()->create(['product_id' => $product->id]);

        $this->actingAs($this->admin)
            ->delete(route('admin.products.images.destroy', [$product, $primary]))
            ->assertRedirect()
            ->assertSessionHasErrors('image');

        $this->assertNotSoftDeleted('product_images', ['id' => $primary->id]);

        $this->actingAs($this->admin)
            ->delete(route('admin.products.images.destroy', [$product, $image]))
            ->assertRedirect();

        $this->assertSoftDeleted('product_images', ['id' => $image->id]);

        $this->actingAs($this->admin)
            ->patch(route('admin.products.images.restore', [$product, $image->id]))
            ->assertRedirect();

        $this->assertNotSoftDeleted('product_images', ['id' => $image->id]);
    }

    public function test_authorization_and_validation_are_enforced(): void
    {
        $product = Product::factory()->create();
        $user = User::factory()->create(['email' => 'customer@example.com']);

        $this->actingAs($user)->post(route('admin.products.images.store', $product), [
            'images' => [UploadedFile::fake()->image('blocked.jpg')],
        ])->assertForbidden();

        $this->actingAs($this->admin)->post(route('admin.products.images.store', $product), [
            'images' => [UploadedFile::fake()->create('bad.pdf', 10, 'application/pdf')],
        ])->assertSessionHasErrors('images.0');
    }

    public function test_image_upload_cleans_new_file_when_database_create_fails(): void
    {
        Storage::fake('uploads');

        $product = Product::factory()->create();
        $repository = Mockery::mock(ProductImageRepositoryInterface::class);
        $repository->shouldReceive('create')->once()->andThrow(new RuntimeException('Database failed.'));

        $service = new ProductImageService($repository, new MediaService);

        try {
            $service->create($product, [
                'images' => [UploadedFile::fake()->image('cleanup.jpg')],
                'status' => 1,
            ]);
            $this->fail('Expected database failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Database failed.', $exception->getMessage());
        }

        $this->assertSame([], Storage::disk('uploads')->allFiles());
    }

    public function test_routes_require_authentication_and_authorization_middleware(): void
    {
        foreach ([
            'admin.products.images.store',
            'admin.products.images.edit',
            'admin.products.images.update',
            'admin.products.images.primary',
            'admin.products.images.destroy',
            'admin.products.images.restore',
            'admin.products.variants.images.store',
            'admin.products.variants.images.edit',
            'admin.products.variants.images.update',
            'admin.products.variants.images.primary',
            'admin.products.variants.images.destroy',
            'admin.products.variants.images.restore',
        ] as $routeName) {
            $middleware = Route::getRoutes()->getByName($routeName)->gatherMiddleware();

            $this->assertContains('auth', $middleware);
            $this->assertContains('can:manage-product-images', $middleware);
        }
    }

    public function test_product_and_variant_tables_keep_transactional_stock_boundaries(): void
    {
        foreach (['sku', 'selling_price', 'mrp', 'purchase_price', 'stock_quantity', 'reserved_quantity', 'available_quantity'] as $column) {
            $this->assertFalse(Schema::hasColumn('products', $column));
        }

        foreach (['stock_quantity', 'reserved_quantity', 'available_quantity'] as $column) {
            $this->assertFalse(Schema::hasColumn('product_variants', $column));
        }
    }
}
