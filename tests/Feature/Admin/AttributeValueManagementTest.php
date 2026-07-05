<?php

namespace Tests\Feature\Admin;

use App\Domains\Catalog\Contracts\AttributeRepositoryInterface;
use App\Domains\Catalog\Contracts\AttributeValueRepositoryInterface;
use App\Domains\Catalog\Services\AttributeValueService;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\User;
use App\Services\SlugService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

class AttributeValueManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);

        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
    }

    public function test_admin_can_view_paginated_attribute_values_with_search_filters(): void
    {
        $weight = Attribute::factory()->create(['name' => 'Weight', 'slug' => 'weight', 'type' => 'weight']);
        $flavor = Attribute::factory()->create(['name' => 'Flavor', 'slug' => 'flavor', 'type' => 'select']);

        AttributeValue::factory()->create(['attribute_id' => $weight->id, 'value' => '500g', 'slug' => '500g', 'status' => true]);
        AttributeValue::factory()->create(['attribute_id' => $flavor->id, 'value' => 'Masala', 'slug' => 'masala', 'status' => false]);

        $response = $this->actingAs($this->admin)->get('/admin/attribute-values?search=500g&attribute_id='.$weight->id.'&status=1');

        $response->assertOk();
        $response->assertSee('500g');
        $response->assertDontSee('Masala');
    }

    public function test_admin_can_create_attribute_value_with_scoped_slug(): void
    {
        $attribute = Attribute::factory()->create(['name' => 'Weight', 'slug' => 'weight', 'type' => 'weight']);

        $response = $this->actingAs($this->admin)->post('/admin/attribute-values', [
            'attribute_id' => $attribute->id,
            'value' => '500g',
            'display_order' => 1,
            'status' => 1,
        ]);

        $response->assertRedirect(route('admin.attribute-values.index'));

        $attributeValue = AttributeValue::query()->where('value', '500g')->firstOrFail();

        $this->assertSame($attribute->id, $attributeValue->attribute_id);
        $this->assertSame('500g', $attributeValue->slug);
        $this->assertTrue($attributeValue->status);
    }

    public function test_same_value_is_allowed_under_different_attributes(): void
    {
        $first = Attribute::factory()->create(['name' => 'Size', 'slug' => 'size', 'type' => 'size']);
        $second = Attribute::factory()->create(['name' => 'Pack Size', 'slug' => 'pack-size', 'type' => 'pack']);

        AttributeValue::factory()->create(['attribute_id' => $first->id, 'value' => 'Small', 'slug' => 'small']);

        $response = $this->actingAs($this->admin)->post('/admin/attribute-values', [
            'attribute_id' => $second->id,
            'value' => 'Small',
            'display_order' => 1,
            'status' => 1,
        ]);

        $response->assertRedirect(route('admin.attribute-values.index'));
        $this->assertSame(2, AttributeValue::query()->where('value', 'Small')->count());
    }

    public function test_duplicate_value_and_slug_are_rejected_within_same_attribute(): void
    {
        $attribute = Attribute::factory()->create(['type' => 'select']);

        AttributeValue::factory()->create([
            'attribute_id' => $attribute->id,
            'value' => 'Plain',
            'slug' => 'plain',
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/attribute-values', [
            'attribute_id' => $attribute->id,
            'value' => 'Plain',
            'slug' => 'plain',
            'status' => 1,
        ]);

        $response->assertSessionHasErrors(['value', 'slug']);
    }

    public function test_active_attribute_value_under_inactive_attribute_is_rejected(): void
    {
        $attribute = Attribute::factory()->create(['type' => 'weight', 'status' => false]);

        $response = $this->actingAs($this->admin)
            ->from('/admin/attribute-values/create')
            ->post('/admin/attribute-values', [
                'attribute_id' => $attribute->id,
                'value' => '1kg',
                'display_order' => 1,
                'status' => 1,
            ]);

        $response->assertRedirect('/admin/attribute-values/create');
        $response->assertSessionHasErrors('attribute_value');
        $this->assertDatabaseMissing('attribute_values', ['value' => '1kg']);
    }

    public function test_attribute_value_respects_parent_attribute_type_rules(): void
    {
        $attribute = Attribute::factory()->create(['type' => 'number']);
        $service = app(AttributeValueService::class);

        $this->expectException(InvalidArgumentException::class);

        $service->create([
            'attribute_id' => $attribute->id,
            'value' => 'not numeric',
            'status' => 1,
        ]);
    }

    public function test_admin_can_update_soft_delete_and_restore_attribute_value(): void
    {
        $attribute = Attribute::factory()->create(['type' => 'volume']);
        $attributeValue = AttributeValue::factory()->create(['attribute_id' => $attribute->id, 'value' => '500ml']);

        $this->actingAs($this->admin)->put('/admin/attribute-values/'.$attributeValue->id, [
            'attribute_id' => $attribute->id,
            'value' => '1L',
            'slug' => '1l',
            'display_order' => 2,
            'status' => 1,
        ])->assertRedirect(route('admin.attribute-values.index'));

        $this->assertSame('1L', $attributeValue->fresh()->value);

        $this->actingAs($this->admin)->delete('/admin/attribute-values/'.$attributeValue->id)
            ->assertRedirect(route('admin.attribute-values.index'));

        $this->assertSoftDeleted('attribute_values', ['id' => $attributeValue->id]);

        $this->actingAs($this->admin)->patch('/admin/attribute-values/'.$attributeValue->id.'/restore')
            ->assertRedirect(route('admin.attribute-values.index', ['trashed' => 'with']));

        $this->assertNotSoftDeleted('attribute_values', ['id' => $attributeValue->id]);
    }

    public function test_bulk_activate_rejects_values_under_inactive_attributes(): void
    {
        $attribute = Attribute::factory()->create(['status' => false]);
        $attributeValue = AttributeValue::factory()->create([
            'attribute_id' => $attribute->id,
            'status' => false,
        ]);

        $this->actingAs($this->admin)
            ->from('/admin/attribute-values')
            ->post('/admin/attribute-values/bulk-action', [
                'ids' => [$attributeValue->id],
                'action' => 'activate',
            ])
            ->assertRedirect('/admin/attribute-values')
            ->assertSessionHasErrors('attribute_value');

        $this->assertFalse($attributeValue->fresh()->status);
    }

    public function test_attribute_value_routes_require_authentication_and_authorization_middleware(): void
    {
        foreach ([
            'admin.attribute-values.index',
            'admin.attribute-values.create',
            'admin.attribute-values.store',
            'admin.attribute-values.show',
            'admin.attribute-values.edit',
            'admin.attribute-values.update',
            'admin.attribute-values.destroy',
            'admin.attribute-values.restore',
            'admin.attribute-values.bulk-action',
        ] as $routeName) {
            $middleware = Route::getRoutes()->getByName($routeName)->gatherMiddleware();

            $this->assertContains('auth', $middleware);
            $this->assertContains('can:manage-attribute-values', $middleware);
        }
    }

    public function test_unauthorized_user_cannot_access_attribute_value_administration(): void
    {
        $user = User::factory()->create(['email' => 'customer@example.com']);
        $attributeValue = AttributeValue::factory()->create();

        $this->actingAs($user)->get('/admin/attribute-values')->assertForbidden();
        $this->actingAs($user)->get('/admin/attribute-values/create')->assertForbidden();
        $this->actingAs($user)->post('/admin/attribute-values', [
            'attribute_id' => $attributeValue->attribute_id,
            'value' => 'Unauthorized Value',
        ])->assertForbidden();
        $this->actingAs($user)->delete('/admin/attribute-values/'.$attributeValue->id)->assertForbidden();
    }

    public function test_attribute_value_service_blocks_deletion_when_future_product_variant_usage_exists(): void
    {
        $attributeValue = AttributeValue::factory()->create();
        $repository = Mockery::mock(AttributeValueRepositoryInterface::class);
        $attributeRepository = Mockery::mock(AttributeRepositoryInterface::class);

        $repository->shouldReceive('idsInUse')
            ->once()
            ->with([$attributeValue->id])
            ->andReturn([$attributeValue->id]);

        $service = new AttributeValueService($repository, $attributeRepository, new SlugService);

        $this->expectException(InvalidArgumentException::class);

        $service->delete($attributeValue);
    }
}
