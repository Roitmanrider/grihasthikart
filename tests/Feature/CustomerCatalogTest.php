<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\DailyOffer;
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
        $response->assertSee('Fresh Groceries');
        $response->assertSee('Daily Offers');
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

    public function test_product_without_active_default_variant_uses_active_sibling(): void
    {
        $visible = $this->catalogProduct(['name' => 'Visible Sugar', 'slug' => 'visible-sugar']);
        $product = $this->catalogProduct(['name' => 'Sibling Sugar', 'slug' => 'sibling-sugar']);
        $product->defaultVariant->update(['status' => false]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'variant_name' => '2kg',
            'attribute_signature' => '2kg',
            'sku' => 'GK-SUGAR-2KG',
            'selling_price' => 180,
            'mrp' => 200,
            'status' => true,
        ]);

        $response = $this->get(route('products.index'));

        $response->assertOk();
        $response->assertSee($visible->name);
        $response->assertSee($product->name);
        $response->assertSee('2kg');
    }

    public function test_product_listing_quick_view_exposes_all_active_variants(): void
    {
        $product = $this->catalogProduct(['name' => 'Basmati Rice', 'slug' => 'basmati-rice'], [
            'variant_name' => '1kg',
            'sku' => 'GK-BASMATI-1KG',
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'variant_name' => '5kg',
            'attribute_signature' => '5kg',
            'sku' => 'GK-BASMATI-5KG',
            'status' => true,
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'variant_name' => '10kg',
            'attribute_signature' => '10kg',
            'sku' => 'GK-BASMATI-10KG',
            'status' => true,
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'variant_name' => '25kg',
            'attribute_signature' => '25kg',
            'sku' => 'GK-BASMATI-25KG',
            'status' => false,
        ]);

        $response = $this->get(route('products.index'));

        $response->assertOk()
            ->assertSee('3 variants available')
            ->assertSee('GK-BASMATI-1KG')
            ->assertSee('GK-BASMATI-5KG')
            ->assertSee('GK-BASMATI-10KG')
            ->assertDontSee('GK-BASMATI-25KG');
    }

    public function test_daily_offer_on_one_variant_does_not_hide_normal_siblings(): void
    {
        $product = $this->catalogProduct(['name' => 'Offer Rice', 'slug' => 'offer-rice'], [
            'variant_name' => '1kg',
            'sku' => 'GK-OFFER-RICE-1KG',
        ]);
        $offerVariant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'variant_name' => '5kg',
            'attribute_signature' => '5kg',
            'sku' => 'GK-OFFER-RICE-5KG',
            'selling_price' => 550,
            'mrp' => 600,
            'status' => true,
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'variant_name' => '10kg',
            'attribute_signature' => '10kg',
            'sku' => 'GK-OFFER-RICE-10KG',
            'status' => true,
        ]);
        DailyOffer::factory()->create([
            'product_variant_id' => $offerVariant->id,
            'offer_price' => 500,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
            'is_active' => true,
        ]);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertOk()
            ->assertSee('GK-OFFER-RICE-1KG')
            ->assertSee('GK-OFFER-RICE-5KG')
            ->assertSee('GK-OFFER-RICE-10KG')
            ->assertSee('DAILY OFFER');
    }

    public function test_no_transactional_customer_routes_are_created(): void
    {
        $uris = collect(Route::getRoutes())->map(fn ($route) => $route->uri())->all();

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
