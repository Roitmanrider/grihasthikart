<?php

namespace Tests\Unit;

use App\Domains\Catalog\Contracts\AttributeRepositoryInterface;
use App\Models\Attribute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttributeRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_attributes_by_search_type_status_and_flags(): void
    {
        Attribute::factory()->create([
            'name' => 'Weight',
            'slug' => 'weight',
            'type' => 'weight',
            'status' => true,
            'is_filterable' => true,
            'is_variant_defining' => true,
        ]);

        Attribute::factory()->create([
            'name' => 'Material',
            'slug' => 'material',
            'type' => 'select',
            'status' => false,
            'is_filterable' => false,
            'is_variant_defining' => false,
        ]);

        $repository = app(AttributeRepositoryInterface::class);

        $results = $repository->paginatedList([
            'search' => 'Weight',
            'type' => 'weight',
            'status' => '1',
            'is_filterable' => '1',
            'is_variant_defining' => '1',
        ], 10);

        $this->assertSame(1, $results->total());
        $this->assertSame('Weight', $results->first()->name);
    }

    public function test_it_can_bulk_update_status_soft_delete_and_restore(): void
    {
        $attributes = Attribute::factory()->count(2)->create(['status' => true]);
        $repository = app(AttributeRepositoryInterface::class);

        $updated = $repository->bulkUpdateStatus($attributes->pluck('id')->all(), false);

        $this->assertSame(2, $updated);
        $this->assertSame(0, Attribute::query()->where('status', true)->count());

        $deleted = $repository->bulkDelete($attributes->pluck('id')->all());

        $this->assertSame(2, $deleted);
        $this->assertSame(2, Attribute::onlyTrashed()->count());

        $restored = $repository->bulkRestore($attributes->pluck('id')->all());

        $this->assertSame(2, $restored);
        $this->assertSame(0, Attribute::onlyTrashed()->count());
    }

    public function test_usage_hook_is_ready_for_future_attribute_values_and_product_variants(): void
    {
        $attribute = Attribute::factory()->create();
        $repository = app(AttributeRepositoryInterface::class);

        $this->assertSame([], $repository->idsInUse([$attribute->id]));
    }
}
