<?php

namespace Tests\Feature\Admin;

use App\Domains\Catalog\Contracts\ProductRepositoryInterface;
use App\Domains\Catalog\Services\ProductService;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\SlugService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);

        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
    }

    public function test_admin_can_view_paginated_products_with_search_and_filters(): void
    {
        $brand = Brand::factory()->create(['name' => 'Aashirvaad']);
        $category = Category::factory()->create(['name' => 'Staples']);
        $product = Product::factory()->create([
            'brand_id' => $brand->id,
            'name' => 'Wheat Atta',
            'slug' => 'wheat-atta',
            'status' => true,
            'is_featured' => true,
        ]);
        $product->categories()->sync([$category->id => ['is_primary' => true, 'display_order' => 0]]);

        Product::factory()->create(['name' => 'Tea', 'slug' => 'tea', 'status' => false]);

        $response = $this->actingAs($this->admin)
            ->get('/admin/products?search=Atta&brand_id='.$brand->id.'&category_id='.$category->id.'&status=1&is_featured=1');

        $response->assertOk();
        $response->assertSee('Wheat Atta');
        $response->assertViewHas('products', fn ($products) => $products->pluck('name')->contains('Wheat Atta')
            && ! $products->pluck('name')->contains('Tea'));
    }

    public function test_admin_can_create_product_with_brand_categories_slug_and_seo(): void
    {
        $brand = Brand::factory()->create(['name' => 'Aashirvaad']);
        $primaryCategory = Category::factory()->create(['name' => 'Staples']);
        $secondaryCategory = Category::factory()->create(['name' => 'Flours']);

        $response = $this->actingAs($this->admin)->post('/admin/products', [
            'brand_id' => $brand->id,
            'category_ids' => [$primaryCategory->id, $secondaryCategory->id],
            'primary_category_id' => $primaryCategory->id,
            'name' => 'Wheat Atta',
            'short_description' => 'Everyday wheat flour.',
            'description' => 'Whole wheat atta for rotis.',
            'barcode' => '8901234567890',
            'hsn_code' => '1101',
            'gst_rate' => '5.00',
            'manufacturer' => 'Aashirvaad Foods',
            'country_of_origin' => 'India',
            'shelf_life' => '12 months',
            'minimum_order_quantity' => 1,
            'maximum_order_quantity' => 10,
            'returnable' => 1,
            'cod_available' => 1,
            'is_featured' => 1,
            'is_trending' => 1,
            'is_popular' => 0,
            'is_new_arrival' => 1,
            'status' => 1,
            'display_order' => 1,
            'meta_title' => 'Wheat Atta Online',
            'meta_description' => 'Buy wheat atta online.',
            'meta_keywords' => 'wheat, atta',
        ]);

        $response->assertRedirect(route('admin.products.index'));

        $product = Product::query()->where('name', 'Wheat Atta')->firstOrFail();

        $this->assertSame('wheat-atta', $product->slug);
        $this->assertSame($brand->id, $product->brand_id);
        $this->assertSame('Wheat Atta Online', $product->meta_title);
        $this->assertTrue($product->is_featured);
        $this->assertCount(2, $product->categories);
        $this->assertSame($primaryCategory->id, $product->categories->firstWhere('pivot.is_primary', true)->id);
    }

    public function test_primary_category_must_be_inside_selected_categories(): void
    {
        $selected = Category::factory()->create();
        $unselected = Category::factory()->create();

        $this->actingAs($this->admin)
            ->from('/admin/products/create')
            ->post('/admin/products', [
                'category_ids' => [$selected->id],
                'primary_category_id' => $unselected->id,
                'name' => 'Invalid Product',
            ])
            ->assertRedirect('/admin/products/create')
            ->assertSessionHasErrors('product');

        $this->assertDatabaseMissing('products', ['name' => 'Invalid Product']);
    }

    public function test_admin_can_update_product_and_category_assignments(): void
    {
        $brand = Brand::factory()->create(['name' => 'India Gate']);
        $oldCategory = Category::factory()->create(['name' => 'Old Category']);
        $newCategory = Category::factory()->create(['name' => 'Rice']);
        $product = Product::factory()->create(['name' => 'Old Product', 'slug' => 'old-product']);
        $product->categories()->sync([$oldCategory->id => ['is_primary' => true, 'display_order' => 0]]);

        $response = $this->actingAs($this->admin)->put('/admin/products/'.$product->id, [
            'brand_id' => $brand->id,
            'category_ids' => [$newCategory->id],
            'primary_category_id' => $newCategory->id,
            'name' => 'Basmati Rice',
            'slug' => 'basmati-rice',
            'minimum_order_quantity' => 1,
            'returnable' => 1,
            'cod_available' => 1,
            'status' => 1,
        ]);

        $response->assertRedirect(route('admin.products.index'));

        $product->refresh();

        $this->assertSame('Basmati Rice', $product->name);
        $this->assertSame('basmati-rice', $product->slug);
        $this->assertSame($brand->id, $product->brand_id);
        $this->assertSame([$newCategory->id], $product->categories()->pluck('categories.id')->all());
    }

    public function test_admin_can_soft_delete_and_restore_product(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin)->delete('/admin/products/'.$product->id)
            ->assertRedirect(route('admin.products.index'));

        $this->assertSoftDeleted('products', ['id' => $product->id]);

        $this->actingAs($this->admin)->patch('/admin/products/'.$product->id.'/restore')
            ->assertRedirect(route('admin.products.index', ['trashed' => 'with']));

        $this->assertNotSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_admin_can_bulk_update_status_delete_and_restore_products(): void
    {
        $products = Product::factory()->count(2)->create(['status' => true]);
        $ids = $products->pluck('id')->all();

        $this->actingAs($this->admin)->post('/admin/products/bulk-action', [
            'ids' => $ids,
            'action' => 'deactivate',
        ])->assertRedirect(route('admin.products.index'));

        $this->assertSame(0, Product::query()->where('status', true)->count());

        $this->actingAs($this->admin)->post('/admin/products/bulk-action', [
            'ids' => $ids,
            'action' => 'delete',
        ])->assertRedirect(route('admin.products.index'));

        $this->assertSame(2, Product::onlyTrashed()->count());

        $this->actingAs($this->admin)->post('/admin/products/bulk-action', [
            'ids' => $ids,
            'action' => 'restore',
        ])->assertRedirect(route('admin.products.index'));

        $this->assertSame(0, Product::onlyTrashed()->count());
    }

    public function test_product_routes_require_authentication_and_authorization_middleware(): void
    {
        foreach ([
            'admin.products.index',
            'admin.products.create',
            'admin.products.store',
            'admin.products.show',
            'admin.products.edit',
            'admin.products.update',
            'admin.products.destroy',
            'admin.products.restore',
            'admin.products.bulk-action',
        ] as $routeName) {
            $middleware = Route::getRoutes()->getByName($routeName)->gatherMiddleware();

            $this->assertContains('auth', $middleware);
            $this->assertContains('can:manage-products', $middleware);
        }
    }

    public function test_unauthorized_user_cannot_access_product_administration(): void
    {
        $user = User::factory()->create(['email' => 'customer@example.com']);
        $product = Product::factory()->create();

        $this->actingAs($user)->get('/admin/products')->assertForbidden();
        $this->actingAs($user)->get('/admin/products/create')->assertForbidden();
        $this->actingAs($user)->post('/admin/products', [
            'name' => 'Unauthorized Product',
        ])->assertForbidden();
        $this->actingAs($user)->delete('/admin/products/'.$product->id)->assertForbidden();
    }

    public function test_service_blocks_deletion_when_future_usage_hook_reports_usage(): void
    {
        $product = Product::factory()->create();
        $repository = Mockery::mock(ProductRepositoryInterface::class);
        $repository->shouldReceive('idsInUse')->once()->with([$product->id])->andReturn([$product->id]);
        $repository->shouldNotReceive('delete');

        $service = new ProductService($repository, new SlugService);

        $this->expectException(InvalidArgumentException::class);

        $service->delete($product);
    }

    public function test_products_table_has_no_variant_transaction_or_inventory_fields(): void
    {
        foreach (['sku', 'selling_price', 'mrp', 'purchase_price', 'stock_quantity', 'quantity', 'inventory'] as $column) {
            $this->assertFalse(Schema::hasColumn('products', $column), $column.' should not exist on products.');
        }
    }

    public function test_product_variant_implementation_is_not_created_in_product_base_milestone(): void
    {
        $this->assertFalse(class_exists('App\\Models\\ProductVariant'));
        $this->assertFalse(Schema::hasTable('product_variants'));
    }
}
