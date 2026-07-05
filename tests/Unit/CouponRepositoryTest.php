<?php

namespace Tests\Unit;

use App\Domains\Coupon\Repositories\CouponRepository;
use App\Models\Coupon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_finds_coupon_by_code(): void
    {
        $coupon = Coupon::factory()->create(['code' => 'SAVE10']);

        $repository = new CouponRepository(new Coupon);

        $this->assertSame($coupon->id, $repository->findByCode('SAVE10')->id);
    }

    public function test_it_filters_coupon_list(): void
    {
        $matched = Coupon::factory()->percentage()->create([
            'code' => 'FRESH10',
            'name' => 'Fresh offer',
            'status' => true,
            'source' => 'promotion',
        ]);
        Coupon::factory()->create(['status' => false]);

        $repository = new CouponRepository(new Coupon);
        $coupons = $repository->paginatedList([
            'search' => 'Fresh',
            'status' => '1',
            'discount_type' => 'percentage',
            'source' => 'promotion',
            'validity' => 'active',
        ]);

        $this->assertTrue($coupons->getCollection()->contains('id', $matched->id));
        $this->assertCount(1, $coupons->getCollection());
    }
}
