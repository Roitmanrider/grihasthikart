<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CustomerCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_homepage_loads_with_sections(): void
    {
        $this->catalogProduct(['is_featured' => true, 'is_new_arrival' => true]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('GrihasthiKart');
        $response->assertSee('Featured Products');
        $response->assertSee('New Arrivals');
    }

    public function test_product_listing_shows_default_variant_price(): void
    {
        $product = $this->catalogProduct(['name' => 'Wheat Atta', 'slug' => 'wheat-atta'], [
            'variant_name' => '1kg',
            'selling_price' => 68,
            'mrp' => 75,
        ]);

        $response = $this->get(route('products.index'));

        $response->assertOk();
        $response->assertSee($product->name);
        $response->assertSee('Rs. 68.00');
        $response->assertSee('1kg');
    }

    public function test_product_listing_filters_by_brand_category_and_search(): void
    {
        $matchingBrand = Brand::factory()->create(['name' => 'Aashirvaad', 'status' => true]);
        $matchingCategory = Category::factory()->create(['name' => 'Staples', 'status' => true]);

        $matching = $this->catalogProduct([
            'brand_id' => $matchingBrand->id,
            'name' => 'Wheat Atta',
            'slug' => 'wheat-atta',
        ], category: $matchingCategory);

        $this->catalogProduct(['name' => 'Assam Tea', 'slug' => 'assam-tea']);

        $response = $this->get(route('products.index', [
            'search' => 'Wheat',
            'brand' => $matchingBrand->id,
            'category' => $matchingCategory->id,
        ]));

        $response->assertOk();
        $response->assertSee($matching->name);
        $response->assertDontSee('Assam Tea');
    }

    public function test_product_detail_shows_active_variants_without_cart_route(): void
    {
        $product = $this->catalogProduct(['name' => 'Basmati Rice', 'slug' => 'basmati-rice'], [
            'variant_name' => '1kg',
            'sku' => 'GK-RICE-1KG',
            'barcode' => '8901000000001',
            'selling_price' => 120,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'variant_name' => '5kg',
            'attribute_signature' => '5kg',
            'sku' => 'GK-RICE-5KG',
            'barcode' => '8901000000005',
            'selling_price' => 575,
            'mrp' => 600,
            'status' => true,
        ]);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertOk();
        $response->assertSee('Basmati Rice');
        $response->assertSee('GK-RICE-1KG');
        $response->assertSee('GK-RICE-5KG');
        $response->assertSee('Add to Cart');
    }

    public function test_category_listing_and_detail_load(): void
    {
        $category = Category::factory()->create(['name' => 'Cooking Oils', 'slug' => 'cooking-oils', 'status' => true]);
        $product = $this->catalogProduct(['name' => 'Sunflower Oil', 'slug' => 'sunflower-oil'], category: $category);

        $this->get(route('categories.index'))
            ->assertOk()
            ->assertSee('Cooking Oils');

        $this->get(route('categories.show', $category->slug))
            ->assertOk()
            ->assertSee('Cooking Oils')
            ->assertSee($product->name);
    }

    public function test_brand_listing_and_detail_load(): void
    {
        $brand = Brand::factory()->create(['name' => 'Fortune', 'slug' => 'fortune', 'status' => true]);
        $product = $this->catalogProduct([
            'brand_id' => $brand->id,
            'name' => 'Sunflower Oil',
            'slug' => 'fortune-sunflower-oil',
        ]);

        $this->get(route('brands.index'))
            ->assertOk()
            ->assertSee('Fortune');

        $this->get(route('brands.show', $brand->slug))
            ->assertOk()
            ->assertSee('Fortune')
            ->assertSee($product->name);
    }

    public function test_inactive_products_are_hidden(): void
    {
        $this->catalogProduct(['name' => 'Visible Salt', 'slug' => 'visible-salt']);
        $this->catalogProduct(['name' => 'Hidden Salt', 'slug' => 'hidden-salt', 'status' => false]);

        $response = $this->get(route('products.index'));

        $response->assertOk();
        $response->assertSee('Visible Salt');
        $response->assertDontSee('Hidden Salt');
    }

    public function test_product_without_active_default_variant_is_hidden(): void
    {
        $visible = $this->catalogProduct(['name' => 'Visible Sugar', 'slug' => 'visible-sugar']);
        $hidden = $this->catalogProduct(['name' => 'Hidden Sugar', 'slug' => 'hidden-sugar']);
        $hidden->defaultVariant->update(['status' => false]);

        $response = $this->get(route('products.index'));

        $response->assertOk();
        $response->assertSee($visible->name);
        $response->assertDontSee($hidden->name);
    }

    public function test_no_transactional_customer_routes_are_created(): void
    {
        $uris = collect(Route::getRoutes())->map(fn ($route) => $route->uri())->all();

        $this->assertNotContains('checkout', $uris);
        $this->assertNotContains('orders', $uris);
        $this->assertNotContains('inventory', $uris);
    }

    private function catalogProduct(array $productOverrides = [], array $variantOverrides = [], ?Brand $brand = null, ?Category $category = null): Product
    {
        $brand ??= Brand::factory()->create(['status' => true]);
        $category ??= Category::factory()->create(['status' => true]);

        $product = Product::factory()->create(array_merge([
            'brand_id' => $brand->id,
            'status' => true,
        ], $productOverrides));

        $product->categories()->sync([
            $category->id => [
                'is_primary' => true,
                'display_order' => 0,
            ],
        ]);

        $variant = ProductVariant::factory()->default()->create(array_merge([
            'product_id' => $product->id,
            'variant_name' => '500g',
            'attribute_signature' => 'default',
            'mrp' => 100,
            'selling_price' => 90,
            'status' => true,
        ], $variantOverrides));

        $product->update(['default_variant_id' => $variant->id]);

        ProductImage::factory()->primary()->create([
            'product_id' => $product->id,
            'product_variant_id' => null,
            'status' => true,
        ]);

        return $product->fresh(['defaultVariant']);
    }
}
