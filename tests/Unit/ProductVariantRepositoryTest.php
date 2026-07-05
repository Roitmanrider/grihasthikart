<?php

namespace Tests\Unit;

use App\Domains\Catalog\Repositories\ProductVariantRepository;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVariantRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_variants_by_product_search_status_and_default_flag(): void
    {
        $product = Product::factory()->create(['name' => 'Wheat Atta']);
        $matched = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'variant_name' => '1kg',
            'sku' => 'GK-ATTA-1KG',
            'attribute_signature' => 'one-kg',
            'status' => true,
            'is_default' => true,
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'variant_name' => '5kg',
            'sku' => 'GK-ATTA-5KG',
            'attribute_signature' => 'five-kg',
            'status' => false,
            'is_default' => false,
        ]);

        $repository = new ProductVariantRepository(new ProductVariant);

        $variants = $repository->forProduct($product->id, [
            'search' => '1KG',
            'status' => 1,
            'is_default' => 1,
        ]);

        $this->assertTrue($variants->getCollection()->contains('id', $matched->id));
        $this->assertCount(1, $variants->getCollection());
    }

    public function test_it_detects_inactive_product_and_attribute_data_for_activation_safety(): void
    {
        $product = Product::factory()->inactive()->create();
        $attribute = Attribute::factory()->inactive()->create(['is_variant_defining' => true]);
        $value = AttributeValue::factory()->create(['attribute_id' => $attribute->id]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        $variant->attributeValues()->sync([$value->id => ['attribute_id' => $attribute->id]]);

        $repository = new ProductVariantRepository(new ProductVariant);

        $this->assertSame([$variant->id], $repository->idsBelongingToInactiveProducts([$variant->id]));
        $this->assertSame([$variant->id], $repository->idsWithInactiveAttributeData([$variant->id]));
    }

    public function test_it_bulk_updates_soft_deletes_and_restores_variants(): void
    {
        $variants = ProductVariant::factory()->count(2)->create(['status' => true]);
        $ids = $variants->pluck('id')->all();

        $repository = new ProductVariantRepository(new ProductVariant);

        $this->assertSame(2, $repository->bulkUpdateStatus($ids, false));
        $this->assertSame(0, ProductVariant::query()->where('status', true)->count());

        $this->assertSame(2, $repository->bulkDelete($ids));
        $this->assertSame(2, ProductVariant::onlyTrashed()->count());

        $this->assertSame(2, $repository->bulkRestore($ids));
        $this->assertSame(0, ProductVariant::onlyTrashed()->count());
    }
}
