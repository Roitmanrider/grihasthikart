<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
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
            'India Gate',
            'Foodgrains',
            'Rice',
            '',
            '5kg',
            'GK-RICE-5KG',
            '650',
            '599',
            '',
            '5',
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
        Category::factory()->create(['name' => 'Flours', 'parent_id' => $category->id]);
        $existingProduct = Product::factory()->create(['name' => 'Existing Product']);
        ProductVariant::factory()->create(['product_id' => $existingProduct->id, 'sku' => 'GK-DUPLICATE']);

        $csv = $this->csv([
            'New Product',
            'Aashirvaad',
            'Foodgrains',
            'Flours',
            '',
            '1kg',
            'GK-DUPLICATE',
            '100',
            '90',
            '',
            '5',
            '1',
            '',
            '1',
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
            ->assertSessionHas('warning');
    }

    public function test_import_requires_default_stock_location_for_opening_stock(): void
    {
        $category = Category::factory()->create(['name' => 'Foodgrains']);
        Category::factory()->create(['name' => 'Staples', 'parent_id' => $category->id]);

        $csv = $this->csv([
            'Sugar',
            'Madhur',
            'Foodgrains',
            'Staples',
            '',
            '1kg',
            'GK-SUGAR-1KG',
            '60',
            '52',
            '',
            '5',
            '',
            '',
            '1',
            'kg',
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
        $category = Category::factory()->create(['name' => 'Foodgrains']);
        Category::factory()->create(['name' => 'Staples', 'parent_id' => $category->id]);

        $csv = $this->csv([
            'Salt',
            'Tata',
            'Foodgrains',
            'Staples',
            '',
            '1kg',
            'GK-SALT-1KG',
            '30',
            '25',
            '',
            '5',
            '1',
            '',
            '1',
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

    public function test_csv_error_report_can_be_downloaded_after_failed_preview(): void
    {
        $category = Category::factory()->create(['name' => 'Foodgrains']);
        Category::factory()->create(['name' => 'Staples', 'parent_id' => $category->id]);

        $csv = $this->csv([
            '',
            'Tata',
            'Foodgrains',
            'Staples',
            '',
            '1kg',
            'GK-ERR-1KG',
            '30',
            '35',
            '',
            '5',
            '',
            '',
            '1',
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
            ->assertSessionHas('warning');

        $response = $this->actingAs($this->admin)->get(route('admin.product-imports.error-report'));

        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename=CSV_Error_Report.csv');
        $rows = $this->parseCsv($response->streamedContent());
        $this->assertSame(['Row Number', 'SKU', 'Column', 'Invalid Value', 'Reason'], $rows[0]);
        $this->assertContains('product_name', array_column(array_slice($rows, 1), 2));
    }

    public function test_duplicate_sku_can_be_skipped(): void
    {
        $category = Category::factory()->create(['name' => 'Foodgrains']);
        Category::factory()->create(['name' => 'Staples', 'parent_id' => $category->id]);
        $product = Product::factory()->create(['name' => 'Salt']);
        ProductVariant::factory()->create(['product_id' => $product->id, 'sku' => 'GK-SALT-1KG', 'variant_name' => '1kg']);

        $csv = $this->validCsvRow('Salt', 'Tata', 'Foodgrains', 'Staples', '1kg', 'GK-SALT-1KG');

        $this->actingAs($this->admin)
            ->post(route('admin.product-imports.preview'), [
                'csv_file' => $this->upload($csv),
                'duplicate_action' => 'skip_existing',
            ])
            ->assertRedirect(route('admin.product-imports.index'))
            ->assertSessionHas('success');

        $this->actingAs($this->admin)
            ->post(route('admin.product-imports.import'))
            ->assertRedirect(route('admin.product-imports.index'))
            ->assertSessionHas('import_summary');

        $this->assertSame(1, ProductVariant::query()->where('sku', 'GK-SALT-1KG')->count());
        $this->assertDatabaseHas('product_import_histories', [
            'filename' => 'products.csv',
            'rows_processed' => 1,
            'rows_skipped' => 1,
            'successful' => true,
        ]);
    }

    public function test_soft_deleted_product_is_not_recreated(): void
    {
        $category = Category::factory()->create(['name' => 'Foodgrains']);
        Category::factory()->create(['name' => 'Staples', 'parent_id' => $category->id]);
        Product::factory()->create(['name' => 'Deleted Salt'])->delete();

        $csv = $this->validCsvRow('Deleted Salt', 'Tata', 'Foodgrains', 'Staples', '1kg', 'GK-DELETED-SALT');

        $this->actingAs($this->admin)
            ->post(route('admin.product-imports.preview'), ['csv_file' => $this->upload($csv)])
            ->assertRedirect(route('admin.product-imports.index'))
            ->assertSessionHas('warning');

        $this->assertSame(0, Product::query()->where('name', 'Deleted Salt')->count());
        $this->assertSame(1, Product::withTrashed()->where('name', 'Deleted Salt')->count());
    }

    public function test_import_rolls_back_when_database_failure_occurs(): void
    {
        $category = Category::factory()->create(['name' => 'Foodgrains']);
        Category::factory()->create(['name' => 'Staples', 'parent_id' => $category->id]);

        $csv = $this->csvRows([
            $this->validRow('Rollback Salt', 'Tata', 'Foodgrains', 'Staples', '1kg', 'GK-ROLLBACK-1KG'),
            $this->validRow('Rollback Sugar', 'Tata', 'Foodgrains', 'Staples', '1kg', 'GK-ROLLBACK-2KG', 'bad.exe'),
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.product-imports.preview'), ['csv_file' => $this->upload($csv)])
            ->assertRedirect(route('admin.product-imports.index'))
            ->assertSessionHas('warning');

        $this->actingAs($this->admin)
            ->post(route('admin.product-imports.import'))
            ->assertRedirect(route('admin.product-imports.index'))
            ->assertSessionHasErrors('import');

        $this->assertDatabaseMissing('products', ['name' => 'Rollback Salt']);
        $this->assertDatabaseMissing('product_variants', ['sku' => 'GK-ROLLBACK-1KG']);
        $this->assertDatabaseHas('product_import_histories', [
            'successful' => false,
            'rows_failed' => 1,
        ]);
    }

    public function test_products_can_be_exported_with_images_and_editable_fields(): void
    {
        $brand = Brand::factory()->create(['name' => 'Tata']);
        $category = Category::factory()->create(['name' => 'Foodgrains']);
        $subcategory = Category::factory()->create(['name' => 'Staples', 'parent_id' => $category->id]);
        $product = Product::factory()->create(['name' => 'Export Salt', 'brand_id' => $brand->id, 'gst_rate' => 5]);
        $product->categories()->sync([$category->id => ['is_primary' => false, 'display_order' => 0], $subcategory->id => ['is_primary' => true, 'display_order' => 1]]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'sku' => 'GK-EXPORT-1KG', 'variant_name' => '1kg', 'barcode' => '8900000000001']);
        ProductImage::factory()->create(['product_id' => $product->id, 'path' => 'uploads/products/export-salt.jpg']);
        ProductImage::factory()->create(['product_id' => $product->id, 'product_variant_id' => $variant->id, 'path' => 'uploads/products/export-salt/variants/1kg.jpg']);

        $response = $this->actingAs($this->admin)->get(route('admin.product-imports.export', ['brand_id' => $brand->id]));

        $response->assertOk();
        $rows = $this->parseCsv($response->streamedContent());
        $this->assertSame(['product_name', 'brand_name', 'category', 'subcategory'], array_slice($rows[0], 0, 4));
        $this->assertSame(['Export Salt', 'Tata', 'Foodgrains', 'Staples'], array_slice($rows[1], 0, 4));
        $this->assertContains('uploads/products/export-salt.jpg', $rows[1]);
        $this->assertContains('uploads/products/export-salt/variants/1kg.jpg', $rows[1]);
    }

    public function test_large_import_preview_summarizes_all_rows(): void
    {
        $category = Category::factory()->create(['name' => 'Foodgrains']);
        Category::factory()->create(['name' => 'Staples', 'parent_id' => $category->id]);

        $rows = [];

        for ($i = 1; $i <= 600; $i++) {
            $rows[] = $this->validRow('Bulk Product '.$i, 'Bulk Brand', 'Foodgrains', 'Staples', '1kg', 'GK-BULK-'.$i);
        }

        $this->actingAs($this->admin)
            ->post(route('admin.product-imports.preview'), ['csv_file' => $this->upload($this->csvRows($rows))])
            ->assertRedirect(route('admin.product-imports.index'))
            ->assertSessionHas('success')
            ->assertSessionHas('product_import.preview.total_rows', 600)
            ->assertSessionHas('product_import.preview.display_limit', 200);
    }

    public function test_product_import_routes_are_authorized(): void
    {
        foreach ([
            'admin.product-imports.index',
            'admin.product-imports.template',
            'admin.product-imports.error-report',
            'admin.product-imports.export',
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
        return $this->csvRows([$row]);
    }

    private function validCsvRow(string $product, string $brand, string $category, string $subcategory, string $variant, string $sku, string $productImage = ''): string
    {
        return $this->csvRows([
            $this->validRow($product, $brand, $category, $subcategory, $variant, $sku, $productImage),
        ]);
    }

    private function validRow(string $product, string $brand, string $category, string $subcategory, string $variant, string $sku, string $productImage = ''): array
    {
        return [
            $product,
            $brand,
            $category,
            $subcategory,
            '',
            $variant,
            $sku,
            '100',
            '90',
            '80',
            '5',
            '1101',
            '',
            '1',
            'kg',
            '',
            '',
            '',
            '',
            '0',
            '0',
            '0',
            '0',
            '1',
            $productImage,
            '',
            '',
            '',
            '',
            '',
            '',
        ];
    }

    private function csvRows(array $rows): string
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
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);

        $content = stream_get_contents($handle);
        fclose($handle);

        return $content;
    }

    private function upload(string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('products.csv', $content);
    }

    private function parseCsv(string $content): array
    {
        return array_map('str_getcsv', preg_split('/\r\n|\r|\n/', trim($content)));
    }
}
