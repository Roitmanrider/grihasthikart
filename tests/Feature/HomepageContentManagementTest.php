<?php

namespace Tests\Feature;

use App\Models\AssociatedPartner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\HomepageBanner;
use App\Models\HomepageSection;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HomepageContentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);
        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
    }

    public function test_default_homepage_works_with_no_config_rows(): void
    {
        $this->createCategoryTree();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Fresh Groceries')
            ->assertSee('All Categories')
            ->assertSee('Fruits & Vegetables')
            ->assertSee('Daily Offers')
            ->assertSee('Our Associated Partners');
    }

    public function test_section_disable_custom_title_limit_and_order_are_respected(): void
    {
        $this->createCategoryTree(3);
        $product = $this->catalogProduct(['name' => 'Newest Salt', 'slug' => 'newest-salt', 'is_new_arrival' => true]);

        HomepageSection::query()->create([
            'section_key' => 'banner_slider',
            'section_type' => 'banner',
            'title' => 'Custom Fresh Store',
            'enabled' => true,
            'sort_order' => 30,
            'desktop_item_limit' => 1,
        ]);
        HomepageSection::query()->create([
            'section_key' => 'new_products',
            'section_type' => 'products',
            'title' => 'Just Added',
            'enabled' => true,
            'sort_order' => 20,
            'desktop_item_limit' => 1,
            'source_mode' => 'automatic',
        ]);
        HomepageSection::query()->create([
            'section_key' => 'daily_offers',
            'section_type' => 'daily_offers',
            'title' => 'Daily Offers',
            'enabled' => false,
            'sort_order' => 10,
            'desktop_item_limit' => 8,
        ]);

        $content = $this->get(route('home'))
            ->assertOk()
            ->assertSee('Just Added')
            ->assertSee($product->name)
            ->assertSee('Custom Fresh Store')
            ->assertDontSee('Daily Offers')
            ->getContent();

        $this->assertLessThan(strpos($content, 'Custom Fresh Store'), strpos($content, 'Just Added'));
    }

    public function test_category_section_manual_selection_skips_deleted_subcategory(): void
    {
        $root = Category::factory()->create(['name' => 'Fresh Produce', 'slug' => 'fresh-produce', 'parent_id' => null, 'status' => true]);
        $visible = Category::factory()->create(['name' => 'Apples', 'slug' => 'apples', 'parent_id' => $root->id, 'status' => true]);
        $deleted = Category::factory()->create(['name' => 'Deleted Pears', 'slug' => 'deleted-pears', 'parent_id' => $root->id, 'status' => true]);
        $deleted->delete();

        $section = HomepageSection::query()->create([
            'section_key' => 'fruits_vegetables',
            'section_type' => 'category_section',
            'title' => 'Fresh Picks',
            'enabled' => true,
            'sort_order' => 25,
            'desktop_item_limit' => 6,
            'root_category_id' => $root->id,
            'source_mode' => 'manual',
        ]);
        $section->selectedCategories()->sync([
            $visible->id => ['sort_order' => 1],
            $deleted->id => ['sort_order' => 2],
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Fresh Picks')
            ->assertSee('Apples')
            ->assertDontSee('Deleted Pears');
    }

    public function test_new_products_manual_mode_skips_unavailable_products(): void
    {
        $visible = $this->catalogProduct(['name' => 'Visible New Flour', 'slug' => 'visible-new-flour']);
        $hidden = $this->catalogProduct(['name' => 'Hidden New Flour', 'slug' => 'hidden-new-flour', 'status' => false]);

        $section = HomepageSection::query()->create([
            'section_key' => 'new_products',
            'section_type' => 'products',
            'title' => 'Manual New Products',
            'enabled' => true,
            'sort_order' => 15,
            'desktop_item_limit' => 8,
            'source_mode' => 'manual',
        ]);
        $section->selectedProducts()->sync([
            $hidden->id => ['sort_order' => 1],
            $visible->id => ['sort_order' => 2],
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Manual New Products')
            ->assertSee('Visible New Flour')
            ->assertDontSee('Hidden New Flour');
    }

    public function test_banner_visibility_mobile_fallback_and_unsafe_url_validation(): void
    {
        HomepageBanner::factory()->create([
            'title' => 'Active Festival Banner',
            'cta_url' => '/products',
            'desktop_image_path' => 'uploads/site/banners/festival.webp',
            'mobile_image_path' => null,
            'enabled' => true,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
        ]);
        HomepageBanner::factory()->create([
            'title' => 'Future Banner',
            'starts_at' => now()->addDay(),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Active Festival Banner')
            ->assertSee('uploads/site/banners/festival.webp')
            ->assertDontSee('Future Banner');

        Storage::fake('uploads');

        $this->actingAs($this->admin)
            ->post(route('admin.homepage.banners.store'), [
                'title' => 'Unsafe Banner',
                'cta_url' => 'javascript:alert(1)',
                'desktop_image' => UploadedFile::fake()->image('banner.webp', 1200, 480),
                'enabled' => 1,
                'sort_order' => 1,
            ])
            ->assertSessionHasErrors('cta_url');
    }

    public function test_partner_visibility_order_and_url_validation(): void
    {
        AssociatedPartner::factory()->create(['name' => 'Hidden Partner', 'enabled' => false, 'sort_order' => 1]);
        AssociatedPartner::factory()->create(['name' => 'Visible Partner', 'enabled' => true, 'sort_order' => 2, 'external_url' => 'https://partner.example']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Visible Partner')
            ->assertSee('rel="noopener noreferrer"', false)
            ->assertDontSee('Hidden Partner');

        $this->actingAs($this->admin)
            ->post(route('admin.homepage.partners.store'), [
                'name' => 'Unsafe Partner',
                'external_url' => 'javascript:alert(1)',
                'enabled' => 1,
                'sort_order' => 1,
            ])
            ->assertSessionHasErrors('external_url');
    }

    public function test_admin_homepage_controls_require_authorized_admin(): void
    {
        $user = User::factory()->create(['email' => 'customer@example.com']);

        $this->actingAs($this->admin)->get(route('admin.homepage.sections.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.homepage.sections.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.homepage.banners.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.homepage.partners.index'))->assertForbidden();
    }

    public function test_duplicate_section_key_is_prevented(): void
    {
        HomepageSection::factory()->create(['section_key' => 'daily_offers']);

        $this->expectException(QueryException::class);

        HomepageSection::factory()->create(['section_key' => 'daily_offers']);
    }

    private function createCategoryTree(int $count = 3): void
    {
        collect(range(1, $count))->each(function (int $index) {
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
            'variant_name' => '500g',
            'attribute_signature' => 'default',
            'mrp' => 100,
            'selling_price' => 90,
            'status' => true,
        ]);

        $product->update(['default_variant_id' => $variant->id]);

        ProductImage::factory()->primary()->create([
            'product_id' => $product->id,
            'product_variant_id' => null,
            'status' => true,
        ]);

        Inventory::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 10,
            'reserved_quantity' => 0,
            'damaged_quantity' => 0,
            'status' => true,
        ]);

        return $product->fresh(['defaultVariant']);
    }
}
