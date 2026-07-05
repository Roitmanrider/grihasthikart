<?php

namespace Tests\Unit;

use App\Domains\Catalog\Repositories\ProductImageRepository;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductImageRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_product_and_variant_images_with_filters(): void
    {
        $product = Product::factory()->create(['name' => 'Wheat Atta']);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        $productImage = ProductImage::factory()->create([
            'product_id' => $product->id,
            'product_variant_id' => null,
            'title' => 'Front Pack',
            'status' => true,
            'is_primary' => true,
        ]);
        ProductImage::factory()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'title' => 'Variant Pack',
            'status' => false,
        ]);

        $repository = new ProductImageRepository(new ProductImage);

        $productImages = $repository->forProduct($product->id, [
            'search' => 'Front',
            'status' => 1,
            'is_primary' => 1,
        ]);
        $variantImages = $repository->forVariant($variant->id);

        $this->assertTrue($productImages->contains('id', $productImage->id));
        $this->assertCount(1, $productImages);
        $this->assertCount(1, $variantImages);
    }

    public function test_it_clears_primary_images_by_scope(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        $productPrimary = ProductImage::factory()->primary()->create(['product_id' => $product->id]);
        $variantPrimary = ProductImage::factory()->primary()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
        ]);

        $repository = new ProductImageRepository(new ProductImage);

        $this->assertSame(1, $repository->clearPrimaryForProduct($product->id));
        $this->assertSame(1, $repository->clearPrimaryForVariant($variant->id));
        $this->assertFalse($productPrimary->fresh()->is_primary);
        $this->assertFalse($variantPrimary->fresh()->is_primary);
    }
}
