<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\DailyOffer;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageUploadAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);
        Storage::fake('uploads');

        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
    }

    public function test_admin_can_upload_category_subcategory_and_sub_subcategory_images(): void
    {
        $parent = $this->createCategory('Fruits', 'fruits');
        $subcategory = $this->createCategory('Fresh Fruits', 'fresh-fruits', $parent);

        $response = $this->actingAs($this->admin)->post(route('admin.categories.store'), [
            'name' => 'Apples',
            'slug' => 'apples',
            'parent_id' => $subcategory->id,
            'image' => UploadedFile::fake()->image('apples.webp'),
            'status' => 1,
        ]);

        $response->assertRedirect(route('admin.categories.index'));

        $category = Category::query()->where('slug', 'apples')->firstOrFail();

        $this->assertStringStartsWith('uploads/categories/image/', $category->image);
        Storage::disk('uploads')->assertExists($category->image);
    }

    public function test_admin_can_upload_brand_logo(): void
    {
        $this->actingAs($this->admin)->post(route('admin.brands.store'), [
            'name' => 'Amul',
            'slug' => 'amul',
            'logo' => UploadedFile::fake()->image('amul.png'),
            'status' => 1,
        ])->assertRedirect(route('admin.brands.index'));

        $brand = Brand::query()->where('slug', 'amul')->firstOrFail();

        $this->assertStringStartsWith('uploads/brands/logo/', $brand->logo);
        Storage::disk('uploads')->assertExists($brand->logo);
    }

    public function test_admin_can_upload_product_and_variant_images(): void
    {
        [$product, $variant] = $this->productWithVariant();

        $this->actingAs($this->admin)->post(route('admin.products.images.store', $product), [
            'images' => [UploadedFile::fake()->image('product.jpg')],
            'is_primary' => 1,
            'status' => 1,
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->admin)->post(route('admin.products.variants.images.store', [$product, $variant]), [
            'images' => [UploadedFile::fake()->image('variant.webp')],
            'is_primary' => 1,
            'status' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertStringStartsWith('uploads/products/'.$product->id.'/', $product->fresh()->primaryImage->path);
        $this->assertStringContainsString('/variants/'.$variant->id.'/', $variant->fresh()->primaryImage->path);
    }

    public function test_invalid_and_oversized_image_uploads_are_rejected(): void
    {
        [$product] = $this->productWithVariant();

        $this->actingAs($this->admin)->post(route('admin.products.images.store', $product), [
            'images' => [UploadedFile::fake()->create('bad.svg', 10, 'image/svg+xml')],
        ])->assertSessionHasErrors('images.0');

        $this->actingAs($this->admin)->post(route('admin.products.images.store', $product), [
            'images' => [UploadedFile::fake()->image('large.jpg')->size(5000)],
        ])->assertSessionHasErrors('images.0');
    }

    public function test_guest_cannot_upload_images_or_site_media(): void
    {
        [$product] = $this->productWithVariant();

        $this->post(route('admin.products.images.store', $product), [
            'images' => [UploadedFile::fake()->image('product.jpg')],
        ])->assertRedirect(route('admin.login'));

        $this->patch(route('admin.settings.site-media.update'), [
            'loading_image' => UploadedFile::fake()->image('loading.jpg'),
        ])->assertRedirect(route('admin.login'));
    }

    public function test_product_card_quick_view_uses_variant_image_before_product_image(): void
    {
        [$product, $variant] = $this->productWithVariant();
        ProductImage::factory()->primary()->create([
            'product_id' => $product->id,
            'product_variant_id' => null,
            'path' => 'uploads/products/product.webp',
            'status' => true,
        ]);
        ProductImage::factory()->primary()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'path' => 'uploads/product-variants/variant.webp',
            'status' => true,
        ]);

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('data-bs-target="#quickViewProduct'.$product->id.'"', false)
            ->assertSee(asset('uploads/product-variants/variant.webp'), false);
    }

    public function test_homepage_subcategory_tile_uses_own_image_before_parent_fallback(): void
    {
        $parent = $this->createCategory('Fruits & Vegetables', 'fruits-vegetables');
        $child = $this->createCategory('Fresh Fruits', 'fresh-fruits', $parent, 'uploads/categories/fresh-fruits.webp');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee($child->name)
            ->assertSee(asset('uploads/categories/fresh-fruits.webp'), false);
    }

    public function test_cart_wishlist_and_daily_offer_use_variant_image(): void
    {
        [$product, $variant] = $this->productWithVariant();
        Inventory::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 20,
            'reserved_quantity' => 0,
            'damaged_quantity' => 0,
        ]);
        ProductImage::factory()->primary()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'path' => 'uploads/product-variants/cart-variant.webp',
            'status' => true,
        ]);

        $this->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->get(route('cart.show'))
            ->assertOk()
            ->assertSee(asset('uploads/product-variants/cart-variant.webp'), false);

        $customer = Customer::factory()->create();
        WishlistItem::factory()->create([
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->get(route('wishlist.index'))
            ->assertOk()
            ->assertSee(asset('uploads/product-variants/cart-variant.webp'), false);

        DailyOffer::factory()->create([
            'product_variant_id' => $variant->id,
            'offer_price' => 20,
            'is_active' => true,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMinute(),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(asset('uploads/product-variants/cart-variant.webp'), false);
    }

    public function test_splash_and_loading_image_settings_can_be_updated(): void
    {
        $this->actingAs($this->admin)->patch(route('admin.settings.site-media.update'), [
            'splash_image' => UploadedFile::fake()->image('splash.png'),
            'loading_image' => UploadedFile::fake()->image('loading.webp'),
        ])->assertRedirect(route('admin.settings.site-media.edit'));

        $this->assertDatabaseHas('business_settings', [
            'group' => 'site',
            'key' => 'splash_image_path',
        ]);
        $this->assertDatabaseHas('business_settings', [
            'group' => 'site',
            'key' => 'loading_image_path',
        ]);
    }

    private function productWithVariant(): array
    {
        $brand = Brand::factory()->create(['status' => true]);
        $category = $this->createCategory('Foodgrains', 'foodgrains', null, 'uploads/categories/foodgrains.webp');
        $product = Product::factory()->create([
            'brand_id' => $brand->id,
            'status' => true,
        ]);
        $product->categories()->sync([$category->id => ['is_primary' => true, 'display_order' => 0]]);
        $variant = ProductVariant::factory()->default()->create([
            'product_id' => $product->id,
            'status' => true,
            'mrp' => 100,
            'selling_price' => 90,
        ]);
        $product->update(['default_variant_id' => $variant->id]);

        return [$product->fresh(['categories', 'primaryImage', 'defaultVariant']), $variant->fresh()];
    }

    private function createCategory(string $name, string $slug, ?Category $parent = null, ?string $image = null): Category
    {
        return Category::factory()->create([
            'name' => $name,
            'slug' => $slug,
            'parent_id' => $parent?->id,
            'image' => $image,
            'status' => true,
            'display_order' => 0,
        ]);
    }
}
