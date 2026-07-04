<?php

namespace Tests\Unit;

use App\Domains\Catalog\Contracts\CategoryRepositoryInterface;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_categories_by_search_status_and_featured_flag(): void
    {
        Category::factory()->create([
            'name' => 'Fresh Vegetables',
            'slug' => 'fresh-vegetables',
            'status' => true,
            'is_featured' => true,
        ]);

        Category::factory()->create([
            'name' => 'Floor Cleaners',
            'slug' => 'floor-cleaners',
            'status' => false,
            'is_featured' => false,
        ]);

        $repository = app(CategoryRepositoryInterface::class);

        $results = $repository->paginatedList([
            'search' => 'Vegetables',
            'status' => '1',
            'is_featured' => '1',
        ], 10);

        $this->assertSame(1, $results->total());
        $this->assertSame('Fresh Vegetables', $results->first()->name);
    }

    public function test_it_can_bulk_update_status_soft_delete_and_restore(): void
    {
        $categories = Category::factory()->count(2)->create(['status' => true]);
        $repository = app(CategoryRepositoryInterface::class);

        $updated = $repository->bulkUpdateStatus($categories->pluck('id')->all(), false);

        $this->assertSame(2, $updated);
        $this->assertSame(0, Category::query()->where('status', true)->count());

        $deleted = $repository->bulkDelete($categories->pluck('id')->all());

        $this->assertSame(2, $deleted);
        $this->assertSame(2, Category::onlyTrashed()->count());

        $restored = $repository->bulkRestore($categories->pluck('id')->all());

        $this->assertSame(2, $restored);
        $this->assertSame(0, Category::onlyTrashed()->count());
    }
}
