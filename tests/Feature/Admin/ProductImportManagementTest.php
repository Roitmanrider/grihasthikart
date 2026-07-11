<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImportManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);

        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
    }

    public function test_admin_can_preview_and_import_products_variants_brand_categories_images_and_inventory(): void
    {
        Storage::fake('uploads');
        Storage::disk('uploads')->put('uploads/products/atta.jpg', 'image');
        Storage::disk('uploads')->put('uploads/products/wheat-atta/variants/1kg/atta-variant.jpg', 'image');

        $category = Category::factory()->create(['name' => 'Foodgrains', 'slug' => 'foodgrains']);
        $subcategory = Category::factory()->create(['name' => 'Flours', 'slug' => 'flours', 'parent_id' => $category->id]);
        StockLocation::factory()->create(['is_default' => true, 'status' => true]);

        $csv = $this->csv([
            'Wheat Atta',
            'Aashirvaad',
            'Foodgrains',
            'Flours',
            '',
            '1kg',
            'GK-ATTA-1KG',
            '80',
            '72',
            '60',
            '5',
            '1101',
            '8901000000001',
            '1',
            'kg',
            '25',
            '5',
            '10',
            '40',
            '1',
            '1',
            '0',
            '1',
            '1',
            'atta.jpg',
            'atta-variant.jpg',
            'Whole wheat atta',
            'Fresh chakki atta',
            'Buy Wheat Atta',
            'Order wheat atta online',
            'atta,wheat',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.product-imports.preview'), ['csv_file' => $this->upload($csv)])
            ->assertRedirect(route('admin.product-imports.index'))
            ->assertSessionHas('success');

        $this->actingAs($this->admin)
            ->post(route('admin.product-imports.import'))
            ->assertRedirect(route('admin.product-imports.index'))
            ->assertSessionHas('success');

        $brand = Brand::query()->where('name', 'Aashirvaad')->firstOrFail();
        $product = Product::query()->where('name', 'Wheat Atta')->firstOrFail();
        $variant = ProductVariant::query()->where('sku', 'GK-ATTA-1KG')->firstOrFail();

        $this->assertSame($brand->id, $product->brand_id);
        $this->assertSame($subcategory->id, $product->categories->firstWhere('pivot.is_primary', true)->id);
        $this->assertSame($product->id, $variant->product_id);
        $this->assertSame('72.00', $variant->selling_price);
        $this->assertSame($variant->id, $product->fresh()->default_variant_id);
        $this->assertDatabaseHas('inventories', [
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 25,
            'low_stock_threshold' => 5,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $variant->id,
            'movement_type' => 'opening',
        ]);
        $this->assertDatabaseHas('product_images', [
            'product_id' => $product->id,
            'product_variant_id' => null,
            'path' => 'uploads/products/atta.jpg',
        ]);
        $this->assertDatabaseHas('product_images', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'path' => 'uploads/products/wheat-atta/variants/1kg/atta-variant.jpg',
        ]);
    }

    public function test_blank_optional_sub_subcategory_is_allowed(): void
    {
        $category = Category::factory()->create(['name' => 'Foodgrains']);
        Category::factory()->create(['name' => 'Rice', 'parent_id' => $category->id]);

        $csv = $this->csv([
            'Basmati Rice',
            '',
            'Foodgrains',
            'Rice',
            '',
            '5kg',
            'GK-RICE-5KG',
            '650',
            '599',
            '',
            '',
            '',
            '',
            '5',
            'kg',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '1',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.product-imports.preview'), ['csv_file' => $this->upload($csv)])
            ->assertRedirect(route('admin.product-imports.index'))
            ->assertSessionHas('success');
    }

    public function test_import_rejects_existing_sku_under_different_product(): void
    {
        $category = Category::factory()->create(['name' => 'Foodgrains']);
        $existingProduct = Product::factory()->create(['name' => 'Existing Product']);
        ProductVariant::factory()->create(['product_id' => $existingProduct->id, 'sku' => 'GK-DUPLICATE']);

        $csv = $this->csv([
            'New Product',
            '',
            'Foodgrains',
            '',
            '',
            '1kg',
            'GK-DUPLICATE',
            '100',
            '90',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '1',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.product-imports.preview'), ['csv_file' => $this->upload($csv)])
            ->assertRedirect(route('admin.product-imports.index'))
            ->assertSessionHas('warning');
    }

    public function test_import_requires_default_stock_location_for_opening_stock(): void
    {
        Category::factory()->create(['name' => 'Foodgrains']);

        $csv = $this->csv([
            'Sugar',
            '',
            'Foodgrains',
            '',
            '',
            '1kg',
            'GK-SUGAR-1KG',
            '60',
            '52',
            '',
            '',
            '',
            '',
            '',
            '',
            '10',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '1',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.product-imports.preview'), ['csv_file' => $this->upload($csv)])
            ->assertRedirect(route('admin.product-imports.index'))
            ->assertSessionHas('warning');
    }

    public function test_import_rejects_missing_image_filename(): void
    {
        Storage::fake('uploads');
        Category::factory()->create(['name' => 'Foodgrains']);

        $csv = $this->csv([
            'Salt',
            '',
            'Foodgrains',
            '',
            '',
            '1kg',
            'GK-SALT-1KG',
            '30',
            '25',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '1',
            'missing.jpg',
            '',
            '',
            '',
            '',
            '',
            '',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.product-imports.preview'), ['csv_file' => $this->upload($csv)])
            ->assertRedirect(route('admin.product-imports.index'))
            ->assertSessionHas('warning');
    }

    public function test_product_import_routes_are_authorized(): void
    {
        foreach ([
            'admin.product-imports.index',
            'admin.product-imports.template',
            'admin.product-imports.preview',
            'admin.product-imports.import',
        ] as $routeName) {
            $middleware = Route::getRoutes()->getByName($routeName)->gatherMiddleware();

            $this->assertContains('auth', $middleware);
            $this->assertContains('can:manage-product-imports', $middleware);
        }

        $user = User::factory()->create(['email' => 'customer@example.com']);

        $this->actingAs($user)->get(route('admin.product-imports.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.product-imports.import'))->assertForbidden();
    }

    public function test_product_and_variant_tables_still_do_not_store_stock_on_catalog_entities(): void
    {
        foreach (['stock_quantity', 'quantity_on_hand', 'reserved_quantity', 'available_quantity'] as $column) {
            $this->assertFalse(Schema::hasColumn('products', $column));
            $this->assertFalse(Schema::hasColumn('product_variants', $column));
        }
    }

    private function csv(array $row): string
    {
        $handle = fopen('php://temp', 'rb+');

        fputcsv($handle, [
            'product_name',
            'brand_name',
            'category',
            'subcategory',
            'sub_subcategory',
            'variant_name',
            'sku',
            'mrp',
            'selling_price',
            'purchase_price',
            'gst_rate',
            'hsn_code',
            'barcode',
            'weight',
            'unit',
            'opening_stock',
            'low_stock_threshold',
            'reorder_level',
            'target_stock_level',
            'is_featured',
            'is_trending',
            'is_popular',
            'is_new_arrival',
            'status',
            'product_image',
            'variant_image',
            'short_description',
            'description',
            'meta_title',
            'meta_description',
            'meta_keywords',
        ]);
        fputcsv($handle, $row);
        rewind($handle);

        $content = stream_get_contents($handle);
        fclose($handle);

        return $content;
    }

    private function upload(string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('products.csv', $content);
    }
}
