<?php

namespace Tests\Unit;

use App\Domains\Catalog\Repositories\DailyOfferRepository;
use App\Models\DailyOffer;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyOfferRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_offers_returns_only_active_valid_offers(): void
    {
        $repository = new DailyOfferRepository(new DailyOffer);
        $currentVariant = $this->variant('GK-CURRENT');
        $expiredVariant = $this->variant('GK-EXPIRED');
        $inactiveVariant = $this->variant('GK-INACTIVE');
        $this->stock($currentVariant);

        $current = DailyOffer::factory()->create([
            'product_variant_id' => $currentVariant->id,
            'offer_price' => 30,
            'is_active' => true,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMinute(),
        ]);
        DailyOffer::factory()->expired()->create(['product_variant_id' => $expiredVariant->id]);
        DailyOffer::factory()->inactive()->create(['product_variant_id' => $inactiveVariant->id]);

        $offers = $repository->currentOffers();

        $this->assertCount(1, $offers);
        $this->assertSame($current->id, $offers->first()->id);
    }

    public function test_active_offer_exists_for_variant_ignores_deleted_records(): void
    {
        $repository = new DailyOfferRepository(new DailyOffer);
        $variant = $this->variant('GK-DUPLICATE');

        $offer = DailyOffer::factory()->create([
            'product_variant_id' => $variant->id,
            'is_active' => true,
        ]);

        $this->assertTrue($repository->activeOfferExistsForVariant($variant->id));
        $this->assertFalse($repository->activeOfferExistsForVariant($variant->id, $offer->id));

        $offer->delete();

        $this->assertFalse($repository->activeOfferExistsForVariant($variant->id));
    }

    private function variant(string $sku): ProductVariant
    {
        $product = Product::factory()->create(['status' => true]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => $sku,
            'status' => true,
            'mrp' => 100,
            'selling_price' => 90,
        ]);

        $product->update(['default_variant_id' => $variant->id]);

        return $variant;
    }

    private function stock(ProductVariant $variant): Inventory
    {
        return Inventory::factory()->create([
            'product_variant_id' => $variant->id,
            'stock_location_id' => StockLocation::factory()->default(),
            'quantity_on_hand' => 10,
            'reserved_quantity' => 0,
            'damaged_quantity' => 0,
            'status' => true,
        ]);
    }
}
