<?php

namespace Tests\Unit;

use App\Domains\Catalog\Repositories\ProductRepository;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_products_by_search_brand_category_and_status(): void
    {
        $brand = Brand::factory()->create(['name' => 'Aashirvaad']);
        $category = Category::factory()->create(['name' => 'Staples']);
        $matched = Product::factory()->create([
            'brand_id' => $brand->id,
            'name' => 'Wheat Atta',
            'slug' => 'wheat-atta',
            'status' => true,
        ]);
        $matched->categories()->sync([$category->id => ['is_primary' => true, 'display_order' => 0]]);

        $otherCategory = Category::factory()->create(['name' => 'Beverages']);
        $other = Product::factory()->create([
            'name' => 'Tea',
            'slug' => 'tea',
            'status' => false,
        ]);
        $other->categories()->sync([$otherCategory->id => ['is_primary' => true, 'display_order' => 0]]);

        $repository = new ProductRepository(new Product);

        $products = $repository->paginatedList([
            'search' => 'Atta',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'status' => 1,
        ]);

        $this->assertTrue($products->getCollection()->contains('id', $matched->id));
        $this->assertFalse($products->getCollection()->contains('id', $other->id));
    }

    public function test_it_bulk_updates_soft_deletes_and_restores_products(): void
    {
        $products = Product::factory()->count(2)->create(['status' => true]);
        $ids = $products->pluck('id')->all();

        $repository = new ProductRepository(new Product);

        $this->assertSame(2, $repository->bulkUpdateStatus($ids, false));
        $this->assertSame(0, Product::query()->where('status', true)->count());

        $this->assertSame(2, $repository->bulkDelete($ids));
        $this->assertSame(2, Product::onlyTrashed()->count());

        $this->assertSame(2, $repository->bulkRestore($ids));
        $this->assertSame(0, Product::onlyTrashed()->count());
    }

    public function test_future_usage_hook_returns_empty_until_variant_and_transaction_modules_exist(): void
    {
        $product = Product::factory()->create();

        $repository = new ProductRepository(new Product);

        $this->assertSame([], $repository->idsInUse([$product->id]));
    }
}
