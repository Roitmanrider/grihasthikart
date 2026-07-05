<?php

namespace Tests\Unit;

use App\Domains\Catalog\Contracts\AttributeValueRepositoryInterface;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttributeValueRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_attribute_values_by_search_attribute_and_status(): void
    {
        $weight = Attribute::factory()->create(['name' => 'Weight', 'slug' => 'weight', 'type' => 'weight']);
        $flavor = Attribute::factory()->create(['name' => 'Flavor', 'slug' => 'flavor', 'type' => 'select']);

        AttributeValue::factory()->create([
            'attribute_id' => $weight->id,
            'value' => '500g',
            'slug' => '500g',
            'status' => true,
        ]);

        AttributeValue::factory()->create([
            'attribute_id' => $flavor->id,
            'value' => 'Masala',
            'slug' => 'masala',
            'status' => false,
        ]);

        $repository = app(AttributeValueRepositoryInterface::class);

        $results = $repository->paginatedList([
            'search' => '500g',
            'attribute_id' => $weight->id,
            'status' => '1',
        ], 10);

        $this->assertSame(1, $results->total());
        $this->assertSame('500g', $results->first()->value);
    }

    public function test_it_can_bulk_update_status_soft_delete_and_restore(): void
    {
        $attributeValues = AttributeValue::factory()->count(2)->create(['status' => true]);
        $repository = app(AttributeValueRepositoryInterface::class);

        $updated = $repository->bulkUpdateStatus($attributeValues->pluck('id')->all(), false);

        $this->assertSame(2, $updated);
        $this->assertSame(0, AttributeValue::query()->where('status', true)->count());

        $deleted = $repository->bulkDelete($attributeValues->pluck('id')->all());

        $this->assertSame(2, $deleted);
        $this->assertSame(2, AttributeValue::onlyTrashed()->count());

        $restored = $repository->bulkRestore($attributeValues->pluck('id')->all());

        $this->assertSame(2, $restored);
        $this->assertSame(0, AttributeValue::onlyTrashed()->count());
    }

    public function test_it_detects_values_under_inactive_attributes(): void
    {
        $attribute = Attribute::factory()->create(['status' => false]);
        $attributeValue = AttributeValue::factory()->create([
            'attribute_id' => $attribute->id,
            'status' => true,
        ]);

        $repository = app(AttributeValueRepositoryInterface::class);

        $this->assertSame([$attributeValue->id], $repository->idsWithInactiveAttributes([$attributeValue->id]));
        $this->assertSame([$attributeValue->id], $repository->activeIdsWithInactiveAttributes([$attributeValue->id]));
    }
}
