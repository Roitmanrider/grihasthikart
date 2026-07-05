<?php

namespace Tests\Feature\Admin;

use App\Domains\Catalog\Contracts\ProductVariantRepositoryInterface;
use App\Domains\Catalog\Services\ProductVariantService;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

class ProductVariantManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);

        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
    }

    public function test_admin_can_create_variant_with_attributes_and_default_flag(): void
    {
        [$product, $attribute, $value] = $this->variantContext();

        $response = $this->actingAs($this->admin)->post(route('admin.products.variants.store', $product), [
            'sku' => 'GK-ATTA-1KG',
            'barcode' => '8901000000001',
            'variant_name' => '1kg',
            'attribute_values' => [$attribute->id => $value->id],
            'weight' => 1,
            'unit' => 'kg',
            'mrp' => 75,
            'selling_price' => 68,
            'purchase_price' => 60,
            'is_default' => 1,
            'status' => 1,
            'display_order' => 1,
        ]);

        $response->assertRedirect(route('admin.products.variants.index', $product));

        $variant = ProductVariant::query()->where('sku', 'GK-ATTA-1KG')->firstOrFail();

        $this->assertSame($product->id, $variant->product_id);
        $this->assertSame((string) $value->id, $variant->attribute_signature);
        $this->assertTrue($variant->is_default);
        $this->assertSame($variant->id, $product->fresh()->default_variant_id);
        $this->assertDatabaseHas('product_variant_attribute_value', [
            'product_variant_id' => $variant->id,
            'attribute_id' => $attribute->id,
            'attribute_value_id' => $value->id,
        ]);
    }

    public function test_sku_and_barcode_must_be_unique_when_barcode_is_present(): void
    {
        $product = Product::factory()->create();
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'GK-DUP',
            'barcode' => '8901000000002',
        ]);

        $this->actingAs($this->admin)
            ->from(route('admin.products.variants.create', $product))
            ->post(route('admin.products.variants.store', $product), [
                'sku' => 'GK-DUP',
                'barcode' => '8901000000002',
                'variant_name' => 'Duplicate',
                'mrp' => 100,
                'selling_price' => 90,
            ])
            ->assertRedirect(route('admin.products.variants.create', $product))
            ->assertSessionHasErrors(['sku', 'barcode']);
    }

    public function test_price_validation_rejects_prices_above_mrp(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.products.variants.store', $product), [
                'sku' => 'GK-PRICE',
                'variant_name' => 'Bad Price',
                'mrp' => 100,
                'selling_price' => 101,
                'purchase_price' => 102,
            ])
            ->assertSessionHasErrors(['selling_price', 'purchase_price']);
    }

    public function test_active_variant_cannot_belong_to_inactive_product(): void
    {
        $product = Product::factory()->inactive()->create();

        $this->actingAs($this->admin)
            ->from(route('admin.products.variants.create', $product))
            ->post(route('admin.products.variants.store', $product), [
                'sku' => 'GK-INACTIVE-PRODUCT',
                'variant_name' => '1kg',
                'mrp' => 100,
                'selling_price' => 90,
                'status' => 1,
            ])
            ->assertRedirect(route('admin.products.variants.create', $product))
            ->assertSessionHasErrors('variant');
    }

    public function test_inactive_attribute_and_value_are_rejected_for_active_variants(): void
    {
        $product = Product::factory()->create();
        $attribute = Attribute::factory()->inactive()->create(['is_variant_defining' => true]);
        $value = AttributeValue::factory()->create(['attribute_id' => $attribute->id]);

        $this->actingAs($this->admin)
            ->post(route('admin.products.variants.store', $product), [
                'sku' => 'GK-INACTIVE-ATTR',
                'variant_name' => '1kg',
                'attribute_values' => [$attribute->id => $value->id],
                'mrp' => 100,
                'selling_price' => 90,
                'status' => 1,
            ])
            ->assertSessionHasErrors('variant');

        $activeAttribute = Attribute::factory()->create(['is_variant_defining' => true]);
        $inactiveValue = AttributeValue::factory()->inactive()->create(['attribute_id' => $activeAttribute->id]);

        $this->actingAs($this->admin)
            ->post(route('admin.products.variants.store', $product), [
                'sku' => 'GK-INACTIVE-VALUE',
                'variant_name' => '5kg',
                'attribute_values' => [$activeAttribute->id => $inactiveValue->id],
                'mrp' => 100,
                'selling_price' => 90,
                'status' => 1,
            ])
            ->assertSessionHasErrors('variant');
    }

    public function test_attribute_value_must_belong_to_submitted_attribute_and_be_variant_defining(): void
    {
        $product = Product::factory()->create();
        $attribute = Attribute::factory()->create(['is_variant_defining' => true]);
        $otherAttribute = Attribute::factory()->create(['is_variant_defining' => true]);
        $value = AttributeValue::factory()->create(['attribute_id' => $otherAttribute->id]);

        $this->actingAs($this->admin)
            ->post(route('admin.products.variants.store', $product), [
                'sku' => 'GK-WRONG-ATTR',
                'variant_name' => 'Wrong',
                'attribute_values' => [$attribute->id => $value->id],
                'mrp' => 100,
                'selling_price' => 90,
            ])
            ->assertSessionHasErrors('variant');

        $nonVariantAttribute = Attribute::factory()->create(['is_variant_defining' => false]);
        $nonVariantValue = AttributeValue::factory()->create(['attribute_id' => $nonVariantAttribute->id]);

        $this->actingAs($this->admin)
            ->post(route('admin.products.variants.store', $product), [
                'sku' => 'GK-NON-VARIANT',
                'variant_name' => 'Non Variant',
                'attribute_values' => [$nonVariantAttribute->id => $nonVariantValue->id],
                'mrp' => 100,
                'selling_price' => 90,
            ])
            ->assertSessionHasErrors('variant');
    }

    public function test_duplicate_attribute_combination_is_rejected_for_same_product(): void
    {
        [$product, $attribute, $value] = $this->variantContext();
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'attribute_signature' => (string) $value->id,
        ])->attributeValues()->sync([$value->id => ['attribute_id' => $attribute->id]]);

        $this->actingAs($this->admin)
            ->post(route('admin.products.variants.store', $product), [
                'sku' => 'GK-DUP-COMBO',
                'variant_name' => 'Duplicate Combo',
                'attribute_values' => [$attribute->id => $value->id],
                'mrp' => 100,
                'selling_price' => 90,
            ])
            ->assertSessionHasErrors('variant');
    }

    public function test_default_switching_clears_previous_default(): void
    {
        $product = Product::factory()->create();
        $attribute = Attribute::factory()->create(['is_variant_defining' => true]);
        $oldValue = AttributeValue::factory()->create(['attribute_id' => $attribute->id, 'value' => '1kg', 'slug' => '1kg']);
        $newValue = AttributeValue::factory()->create(['attribute_id' => $attribute->id, 'value' => '5kg', 'slug' => '5kg']);
        $oldDefault = ProductVariant::factory()->default()->create([
            'product_id' => $product->id,
            'attribute_signature' => (string) $oldValue->id,
        ]);
        $oldDefault->attributeValues()->sync([$oldValue->id => ['attribute_id' => $attribute->id]]);
        $product->forceFill(['default_variant_id' => $oldDefault->id])->save();
        $newVariant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'attribute_signature' => (string) $newValue->id,
        ]);
        $newVariant->attributeValues()->sync([$newValue->id => ['attribute_id' => $attribute->id]]);

        $this->actingAs($this->admin)->put(route('admin.products.variants.update', [$product, $newVariant]), [
            'sku' => $newVariant->sku,
            'variant_name' => $newVariant->variant_name,
            'attribute_values' => [$attribute->id => $newValue->id],
            'mrp' => $newVariant->mrp,
            'selling_price' => $newVariant->selling_price,
            'is_default' => 1,
            'status' => 1,
        ])->assertRedirect(route('admin.products.variants.index', $product));

        $this->assertFalse($oldDefault->fresh()->is_default);
        $this->assertTrue($newVariant->fresh()->is_default);
        $this->assertSame($newVariant->id, $product->fresh()->default_variant_id);
    }

    public function test_default_variant_delete_is_protected_unless_inactive_only_variant(): void
    {
        $product = Product::factory()->create();
        $default = ProductVariant::factory()->default()->create(['product_id' => $product->id, 'status' => true]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'attribute_signature' => 'other']);
        $product->forceFill(['default_variant_id' => $default->id])->save();

        $this->actingAs($this->admin)
            ->from(route('admin.products.variants.index', $product))
            ->delete(route('admin.products.variants.destroy', [$product, $default]))
            ->assertRedirect(route('admin.products.variants.index', $product))
            ->assertSessionHasErrors('variant');

        $singleProduct = Product::factory()->create();
        $inactiveDefault = ProductVariant::factory()->default()->inactive()->create(['product_id' => $singleProduct->id]);
        $singleProduct->forceFill(['default_variant_id' => $inactiveDefault->id])->save();

        $this->actingAs($this->admin)
            ->delete(route('admin.products.variants.destroy', [$singleProduct, $inactiveDefault]))
            ->assertRedirect(route('admin.products.variants.index', $singleProduct));

        $this->assertSoftDeleted('product_variants', ['id' => $inactiveDefault->id]);
        $this->assertNull($singleProduct->fresh()->default_variant_id);
    }

    public function test_admin_can_soft_delete_restore_and_bulk_update_variants(): void
    {
        $product = Product::factory()->create();
        $first = ProductVariant::factory()->create(['product_id' => $product->id, 'status' => true, 'attribute_signature' => 'first']);
        $second = ProductVariant::factory()->create(['product_id' => $product->id, 'status' => true, 'attribute_signature' => 'second']);
        $ids = [$first->id, $second->id];

        $this->actingAs($this->admin)->post(route('admin.products.variants.bulk-action', $product), [
            'ids' => $ids,
            'action' => 'deactivate',
        ])->assertRedirect(route('admin.products.variants.index', $product));

        $this->assertSame(0, ProductVariant::query()->where('status', true)->count());

        $this->actingAs($this->admin)->post(route('admin.products.variants.bulk-action', $product), [
            'ids' => $ids,
            'action' => 'delete',
        ])->assertRedirect(route('admin.products.variants.index', $product));

        $this->assertSame(2, ProductVariant::onlyTrashed()->count());

        $this->actingAs($this->admin)->post(route('admin.products.variants.bulk-action', $product), [
            'ids' => $ids,
            'action' => 'restore',
        ])->assertRedirect(route('admin.products.variants.index', $product));

        $this->assertSame(0, ProductVariant::onlyTrashed()->count());
    }

    public function test_variant_routes_require_authentication_and_authorization_middleware(): void
    {
        foreach ([
            'admin.products.variants.index',
            'admin.products.variants.create',
            'admin.products.variants.store',
            'admin.products.variants.show',
            'admin.products.variants.edit',
            'admin.products.variants.update',
            'admin.products.variants.destroy',
            'admin.products.variants.restore',
            'admin.products.variants.bulk-action',
        ] as $routeName) {
            $middleware = Route::getRoutes()->getByName($routeName)->gatherMiddleware();

            $this->assertContains('auth', $middleware);
            $this->assertContains('can:manage-product-variants', $middleware);
        }
    }

    public function test_unauthorized_user_cannot_access_variant_administration(): void
    {
        $user = User::factory()->create(['email' => 'customer@example.com']);
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        $this->actingAs($user)->get(route('admin.products.variants.index', $product))->assertForbidden();
        $this->actingAs($user)->get(route('admin.products.variants.create', $product))->assertForbidden();
        $this->actingAs($user)->post(route('admin.products.variants.store', $product), [
            'sku' => 'GK-BLOCKED',
        ])->assertForbidden();
        $this->actingAs($user)->delete(route('admin.products.variants.destroy', [$product, $variant]))->assertForbidden();
    }

    public function test_service_blocks_deletion_when_future_usage_hook_reports_usage(): void
    {
        $variant = ProductVariant::factory()->create();
        $repository = Mockery::mock(ProductVariantRepositoryInterface::class);
        $repository->shouldReceive('idsInUse')->once()->with([$variant->id])->andReturn([$variant->id]);
        $repository->shouldNotReceive('delete');

        $service = new ProductVariantService($repository);

        $this->expectException(InvalidArgumentException::class);

        $service->delete($variant);
    }

    public function test_product_variants_table_has_no_stock_fields_and_products_keep_no_price_sku_stock_fields(): void
    {
        foreach (['stock_quantity', 'reserved_quantity', 'available_quantity', 'inventory_quantity'] as $column) {
            $this->assertFalse(Schema::hasColumn('product_variants', $column), $column.' should not exist on product variants.');
        }

        foreach (['sku', 'selling_price', 'mrp', 'purchase_price', 'stock_quantity', 'reserved_quantity', 'available_quantity'] as $column) {
            $this->assertFalse(Schema::hasColumn('products', $column), $column.' should not exist on products.');
        }
    }

    private function variantContext(): array
    {
        $product = Product::factory()->create();
        $attribute = Attribute::factory()->create([
            'name' => 'Weight',
            'slug' => 'weight',
            'is_variant_defining' => true,
            'status' => true,
        ]);
        $value = AttributeValue::factory()->create([
            'attribute_id' => $attribute->id,
            'value' => '1kg',
            'slug' => '1kg',
            'status' => true,
        ]);

        return [$product, $attribute, $value];
    }
}
