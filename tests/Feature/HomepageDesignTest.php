<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageDesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_matches_approved_section_order_and_hides_old_product_sections(): void
    {
        $this->createCategoryTree();
        $this->catalogProduct(['name' => 'Banana Robusta', 'slug' => 'banana-robusta', 'is_featured' => true]);

        $response = $this->get(route('home'))->assertOk();
        $content = $response->getContent();

        $response->assertSee('All Categories')
            ->assertSee('View More Categories')
            ->assertSee('Daily Offers')
            ->assertSee('Free Delivery')
            ->assertSee('Our Associated Partners')
            ->assertDontSee('Shop by Categories')
            ->assertDontSee('Featured Products')
            ->assertDontSee('New Arrivals')
            ->assertDontSee('Trending Products')
            ->assertDontSee('Popular Products');

        $this->assertStringOrder($content, [
            'Fresh Groceries',
            'All Categories',
            'Fruits & Vegetables',
            'View More Categories',
            'Daily Offers',
            'Scheduled Delivery',
            'Our Associated Partners',
        ]);
    }

    public function test_homepage_renders_up_to_nine_category_sections_when_data_exists(): void
    {
        $parents = $this->createCategoryTree(10);

        $response = $this->get(route('home'))->assertOk();
        $content = $response->getContent();

        foreach ($parents->take(9) as $parent) {
            $response->assertSee($parent->name);
        }

        $this->assertSame(9, substr_count($content, 'data-home-category-section'));
    }

    public function test_footer_policy_links_still_point_to_real_routes(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('pages.privacy'), false)
            ->assertSee(route('pages.terms'), false)
            ->assertSee(route('pages.shipping'), false)
            ->assertSee(route('pages.returns'), false)
            ->assertSee(route('pages.disclaimer'), false);
    }

    private function createCategoryTree(int $count = 3)
    {
        return collect(range(1, $count))->map(function (int $index) {
            $parent = Category::factory()->create([
                'name' => match ($index) {
                    1 => 'Fruits & Vegetables',
                    2 => 'Foodgrains, Flours & Rice',
                    3 => 'Face, Body & Hair Care',
                    default => 'Homepage Category '.$index,
                },
                'slug' => 'homepage-category-'.$index,
                'parent_id' => null,
                'status' => true,
                'display_order' => $index,
            ]);

            foreach (range(1, 3) as $childIndex) {
                Category::factory()->create([
                    'name' => $parent->name.' Child '.$childIndex,
                    'slug' => 'homepage-category-'.$index.'-child-'.$childIndex,
                    'parent_id' => $parent->id,
                    'status' => true,
                    'display_order' => $childIndex,
                ]);
            }

            return $parent;
        });
    }

    private function catalogProduct(array $productOverrides = []): Product
    {
        $brand = Brand::factory()->create(['status' => true]);
        $category = Category::factory()->create(['status' => true]);
        $product = Product::factory()->create(array_merge([
            'brand_id' => $brand->id,
            'status' => true,
        ], $productOverrides));

        $product->categories()->sync([
            $category->id => ['is_primary' => true, 'display_order' => 0],
        ]);

        $variant = ProductVariant::factory()->default()->create([
            'product_id' => $product->id,
            'variant_name' => '1kg',
            'mrp' => 40,
            'selling_price' => 30,
            'status' => true,
        ]);

        $product->update(['default_variant_id' => $variant->id]);

        ProductImage::factory()->primary()->create([
            'product_id' => $product->id,
            'product_variant_id' => null,
            'status' => true,
        ]);

        return $product;
    }

    private function assertStringOrder(string $content, array $needles): void
    {
        $lastPosition = -1;

        foreach ($needles as $needle) {
            $position = strpos($content, $needle);
            $escapedNeedle = e($needle);

            if ($position === false && $escapedNeedle !== $needle) {
                $position = strpos($content, $escapedNeedle);
            }

            $this->assertNotFalse($position, 'Could not find '.$needle.' in homepage content.');
            $this->assertGreaterThan($lastPosition, $position, $needle.' appeared out of order.');

            $lastPosition = $position;
        }
    }
}
