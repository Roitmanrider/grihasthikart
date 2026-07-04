<?php

namespace Tests\Unit;

use App\Domains\Catalog\Contracts\BrandRepositoryInterface;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_brands_by_search_status_and_featured_flag(): void
    {
        Brand::factory()->create([
            'name' => 'Amul',
            'slug' => 'amul',
            'status' => true,
            'is_featured' => true,
        ]);

        Brand::factory()->create([
            'name' => 'Floor Cleaner Brand',
            'slug' => 'floor-cleaner-brand',
            'status' => false,
            'is_featured' => false,
        ]);

        $repository = app(BrandRepositoryInterface::class);

        $results = $repository->paginatedList([
            'search' => 'Amul',
            'status' => '1',
            'is_featured' => '1',
        ], 10);

        $this->assertSame(1, $results->total());
        $this->assertSame('Amul', $results->first()->name);
    }

    public function test_it_can_bulk_update_status_soft_delete_and_restore(): void
    {
        $brands = Brand::factory()->count(2)->create(['status' => true]);
        $repository = app(BrandRepositoryInterface::class);

        $updated = $repository->bulkUpdateStatus($brands->pluck('id')->all(), false);

        $this->assertSame(2, $updated);
        $this->assertSame(0, Brand::query()->where('status', true)->count());

        $deleted = $repository->bulkDelete($brands->pluck('id')->all());

        $this->assertSame(2, $deleted);
        $this->assertSame(2, Brand::onlyTrashed()->count());

        $restored = $repository->bulkRestore($brands->pluck('id')->all());

        $this->assertSame(2, $restored);
        $this->assertSame(0, Brand::onlyTrashed()->count());
    }
}
