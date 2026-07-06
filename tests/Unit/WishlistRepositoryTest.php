<?php

namespace Tests\Unit;

use App\Domains\Wishlist\Repositories\WishlistRepository;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\WishlistItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_counts_and_finds_customer_wishlist_items(): void
    {
        $customer = Customer::factory()->create();
        $otherCustomer = Customer::factory()->create();
        [$product, $variant] = $this->activeVariant();
        [, $otherVariant] = $this->activeVariant();
        $item = WishlistItem::factory()->create([
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
        ]);
        WishlistItem::factory()->create([
            'customer_id' => $otherCustomer->id,
            'product_id' => $otherVariant->product_id,
            'product_variant_id' => $otherVariant->id,
        ]);

        $repository = new WishlistRepository(new WishlistItem);

        $items = $repository->forCustomer($customer);

        $this->assertSame(1, $items->total());
        $this->assertSame(1, $repository->countForCustomer($customer));
        $this->assertSame($item->id, $repository->findForCustomer($customer, $item->id)->id);
        $this->assertSame($item->id, $repository->findExistingForCustomer($customer, $variant->id)?->id);
    }

    public function test_it_creates_and_soft_deletes_customer_wishlist_items(): void
    {
        $customer = Customer::factory()->create();
        [, $variant] = $this->activeVariant();
        $repository = new WishlistRepository(new WishlistItem);

        $item = $repository->createForCustomer($customer, $variant);

        $this->assertSame($customer->id, $item->customer_id);
        $this->assertSame($variant->id, $item->product_variant_id);
        $this->assertTrue($repository->removeForCustomer($customer, $item));
        $this->assertSoftDeleted('wishlist_items', ['id' => $item->id]);
    }

    private function activeVariant(): array
    {
        $product = Product::factory()->create(['status' => true]);
        $variant = ProductVariant::factory()->default()->create([
            'product_id' => $product->id,
            'status' => true,
        ]);
        $product->update(['default_variant_id' => $variant->id]);

        return [$product, $variant];
    }
}
