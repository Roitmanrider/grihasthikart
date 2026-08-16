<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchCatalogDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_search_matches_product_category_subcategory_brand_and_variant_weight(): void
    {
        $rice = Category::factory()->create(['name' => 'Rice', 'slug' => 'rice', 'status' => true]);
        $basmati = Category::factory()->create(['name' => 'Basmati Rice', 'slug' => 'basmati-rice', 'parent_id' => $rice->id, 'status' => true]);
        $brand = Brand::factory()->create(['name' => 'India Gate', 'slug' => 'india-gate', 'status' => true]);
        $product = $this->catalogProduct([
            'name' => 'India Gate Basmati Rice',
            'slug' => 'india-gate-basmati-rice',
            'brand_id' => $brand->id,
        ], [
            'variant_name' => '5kg',
            'weight' => 5,
            'unit' => 'kg',
            'sku' => 'GK-RICE-5KG',
        ], $basmati);

        $this->get(route('products.index', ['q' => '  BASMATI   rice  ']))
            ->assertOk()
            ->assertSee('India Gate Basmati Rice')
            ->assertSee('Related categories')
            ->assertSee('Basmati Rice');

        $this->get(route('products.index', ['q' => 'india gate']))
            ->assertOk()
            ->assertSee($product->name);

        $this->get(route('products.index', ['q' => 'atta 5 kg']))
            ->assertOk();

        $this->get(route('products.index', ['q' => '5kg']))
            ->assertOk()
            ->assertSee($product->name);
    }

    public function test_hidden_deleted_and_inactive_brand_products_are_excluded_but_out_of_stock_policy_matches_catalog(): void
    {
        $visible = $this->catalogProduct(['name' => 'Visible Salt', 'slug' => 'visible-salt']);
        $hidden = $this->catalogProduct(['name' => 'Hidden Salt', 'slug' => 'hidden-salt', 'status' => false]);
        $deleted = $this->catalogProduct(['name' => 'Deleted Salt', 'slug' => 'deleted-salt']);
        $deleted->delete();
        $inactiveBrand = Brand::factory()->create(['name' => 'Inactive Brand', 'status' => false]);
        $this->catalogProduct(['name' => 'Inactive Brand Salt', 'slug' => 'inactive-brand-salt', 'brand_id' => $inactiveBrand->id]);
        $outOfStock = $this->catalogProduct(['name' => 'Out Stock Salt', 'slug' => 'out-stock-salt'], inventory: 0);

        $this->get(route('products.index', ['q' => 'salt']))
            ->assertOk()
            ->assertSee($visible->name)
            ->assertSee($outOfStock->name)
            ->assertSee('Out of Stock')
            ->assertDontSee($hidden->name)
            ->assertDontSee($deleted->name)
            ->assertDontSee('Inactive Brand Salt');
    }

    public function test_empty_and_excessive_search_query_are_safe(): void
    {
        $this->catalogProduct(['name' => 'Safe Product', 'slug' => 'safe-product']);

        $this->get(route('products.index', ['q' => '   ']))
            ->assertOk()
            ->assertSee('Safe Product');

        $this->get(route('products.index', ['q' => str_repeat('rice ', 60)]))
            ->assertOk();
    }

    public function test_autocomplete_requires_minimum_query_caps_results_and_excludes_private_content(): void
    {
        Category::factory()->create(['name' => 'Rice', 'slug' => 'rice', 'status' => true]);
        Brand::factory()->create(['name' => 'Rice Brand', 'slug' => 'rice-brand', 'status' => true]);

        foreach (range(1, 12) as $index) {
            $this->catalogProduct(['name' => 'Rice Product '.$index, 'slug' => 'rice-product-'.$index]);
        }

        $hidden = $this->catalogProduct(['name' => 'Rice Hidden Product', 'slug' => 'rice-hidden-product', 'status' => false]);

        $this->getJson(route('products.autocomplete', ['q' => 'r']))
            ->assertOk()
            ->assertJson(['data' => []]);

        $payload = $this->getJson(route('products.autocomplete', ['q' => 'rice']))
            ->assertOk()
            ->json('data');

        $this->assertLessThanOrEqual(10, count($payload));
        $this->assertContains('category', collect($payload)->pluck('type')->all());
        $this->assertContains('product', collect($payload)->pluck('type')->all());
        $this->assertNotContains($hidden->name, collect($payload)->pluck('label')->all());
    }

    public function test_header_autocomplete_groups_suggestions_and_mobile_search_button_stays_icon_sized(): void
    {
        $this->catalogProduct(['name' => 'Grouped Search Rice', 'slug' => 'grouped-search-rice']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-catalog-search', false)
            ->assertSee('data-catalog-suggestions', false)
            ->assertSee('gk-search-mobile', false);

        $searchScript = file_get_contents(public_path('assets/js/catalog-search.js'));
        $searchStyles = file_get_contents(public_path('assets/css/style.css'));

        $this->assertStringContainsString('gk-search-suggestion-group', $searchScript);
        $this->assertStringContainsString('gk-search-suggestion-heading', $searchScript);
        $this->assertStringContainsString('Products', $searchScript);
        $this->assertStringContainsString('Categories/Subcategories', $searchScript);
        $this->assertStringContainsString('Brands', $searchScript);
        $this->assertStringContainsString('.gk-search-mobile button', $searchStyles);
        $this->assertStringContainsString('width: 44px', $searchStyles);
    }

    public function test_filters_combine_with_and_semantics_and_multi_brand_uses_or(): void
    {
        $tata = Brand::factory()->create(['name' => 'Tata', 'status' => true]);
        $fortune = Brand::factory()->create(['name' => 'Fortune', 'status' => true]);
        $amul = Brand::factory()->create(['name' => 'Amul', 'status' => true]);

        $matching = $this->catalogProduct(['name' => 'Tata Discount Atta', 'slug' => 'tata-discount-atta', 'brand_id' => $tata->id], [
            'variant_name' => '5kg',
            'mrp' => 500,
            'selling_price' => 400,
        ]);
        $alsoBrand = $this->catalogProduct(['name' => 'Fortune Discount Atta', 'slug' => 'fortune-discount-atta', 'brand_id' => $fortune->id], [
            'variant_name' => '5kg',
            'mrp' => 600,
            'selling_price' => 450,
        ]);
        $wrongBrand = $this->catalogProduct(['name' => 'Amul Discount Atta', 'slug' => 'amul-discount-atta', 'brand_id' => $amul->id], [
            'variant_name' => '5kg',
            'mrp' => 500,
            'selling_price' => 400,
        ]);
        $tooExpensive = $this->catalogProduct(['name' => 'Tata Premium Atta', 'slug' => 'tata-premium-atta', 'brand_id' => $tata->id], [
            'variant_name' => '5kg',
            'mrp' => 900,
            'selling_price' => 800,
        ]);

        $this->get(route('products.index', [
            'q' => 'atta',
            'brand' => [$tata->id, $fortune->id],
            'min_price' => 350,
            'max_price' => 500,
            'weight' => ['5kg'],
            'discount_min' => 10,
        ]))
            ->assertOk()
            ->assertSee($matching->name)
            ->assertSee($alsoBrand->name)
            ->assertDontSee($wrongBrand->name)
            ->assertDontSee($tooExpensive->name);
    }

    public function test_sort_options_are_deterministic(): void
    {
        $cheap = $this->catalogProduct(['name' => 'Alpha Rice', 'slug' => 'alpha-rice'], ['mrp' => 100, 'selling_price' => 90]);
        $discounted = $this->catalogProduct(['name' => 'Beta Rice', 'slug' => 'beta-rice'], ['mrp' => 200, 'selling_price' => 100]);
        $newest = $this->catalogProduct(['name' => 'Gamma Rice', 'slug' => 'gamma-rice', 'created_at' => now()->addMinute()], ['mrp' => 300, 'selling_price' => 280]);

        $this->assertOrder(route('products.index', ['q' => 'rice', 'sort' => 'price_asc']), [$cheap->name, $discounted->name]);
        $this->assertOrder(route('products.index', ['q' => 'rice', 'sort' => 'price_desc']), [$newest->name, $discounted->name]);
        $this->assertOrder(route('products.index', ['q' => 'rice', 'sort' => 'discount_desc']), [$discounted->name, $cheap->name]);
        $this->assertOrder(route('products.index', ['q' => 'rice', 'sort' => 'name']), [$cheap->name, $discounted->name, $newest->name]);
        $this->assertOrder(route('products.index', ['q' => 'rice', 'sort' => 'latest']), [$newest->name, $discounted->name]);
    }

    public function test_category_context_pagination_preserves_query_state_and_quick_view_cart_markup(): void
    {
        $category = Category::factory()->create(['name' => 'Staples', 'slug' => 'staples', 'status' => true]);

        foreach (range(1, 14) as $index) {
            $this->catalogProduct(['name' => 'Staples Product '.$index, 'slug' => 'staples-product-'.$index], category: $category);
        }

        $response = $this->get(route('categories.show', [
            'slug' => $category->slug,
            'q' => 'staples',
            'sort' => 'name',
        ]));

        $response->assertOk()
            ->assertSee('quickViewProduct', false)
            ->assertSee('Add to Cart')
            ->assertSee('pagination', false)
            ->assertSee('page-item', false)
            ->assertSee('q=staples', false)
            ->assertSee('sort=name', false)
            ->assertDontSee('w-5 h-5', false);
    }

    public function test_search_and_filter_pages_are_noindexed(): void
    {
        $this->catalogProduct(['name' => 'Noindex Rice', 'slug' => 'noindex-rice']);

        $this->get(route('products.index', ['q' => 'rice']))
            ->assertOk()
            ->assertSee('noindex', false);
    }

    private function catalogProduct(array $productOverrides = [], array $variantOverrides = [], ?Category $category = null, float $inventory = 10): Product
    {
        $brand = isset($productOverrides['brand_id'])
            ? Brand::query()->find($productOverrides['brand_id'])
            : Brand::factory()->create(['status' => true]);
        $category ??= Category::factory()->create(['status' => true]);

        $product = Product::factory()->create(array_merge([
            'brand_id' => $brand->id,
            'status' => true,
        ], $productOverrides));

        $product->categories()->sync([
            $category->id => ['is_primary' => true, 'display_order' => 0],
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

        Inventory::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => $inventory,
            'reserved_quantity' => 0,
            'damaged_quantity' => 0,
            'status' => true,
        ]);

        return $product->fresh(['defaultVariant']);
    }

    private function assertOrder(string $url, array $needles): void
    {
        $content = $this->get($url)->assertOk()->getContent();
        $last = -1;

        foreach ($needles as $needle) {
            $position = strpos($content, $needle);
            $this->assertNotFalse($position, 'Could not find '.$needle);
            $this->assertGreaterThan($last, $needle.' appeared out of order.');
            $last = $position;
        }
    }
}
