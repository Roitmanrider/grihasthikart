<?php

namespace Tests\Feature\Admin;

use App\Domains\Catalog\Contracts\AttributeRepositoryInterface;
use App\Domains\Catalog\Services\AttributeService;
use App\Models\Attribute;
use App\Models\User;
use App\Services\SlugService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

class AttributeManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);

        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
    }

    public function test_admin_can_view_paginated_attributes_with_search_filters(): void
    {
        Attribute::factory()->create([
            'name' => 'Weight',
            'slug' => 'weight',
            'type' => 'weight',
            'status' => true,
            'is_variant_defining' => true,
        ]);

        Attribute::factory()->create([
            'name' => 'Flavor',
            'slug' => 'flavor',
            'type' => 'select',
            'status' => false,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/attributes?search=Weight&type=weight&status=1&is_variant_defining=1');

        $response->assertOk();
        $response->assertSee('Weight');
        $response->assertDontSee('Flavor');
    }

    public function test_admin_can_create_attribute_with_generated_slug(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/attributes', [
            'name' => 'Pack Size',
            'type' => 'pack',
            'display_order' => 1,
            'status' => 1,
            'is_filterable' => 1,
            'is_variant_defining' => 1,
        ]);

        $response->assertRedirect(route('admin.attributes.index'));

        $attribute = Attribute::query()->where('name', 'Pack Size')->firstOrFail();

        $this->assertSame('pack-size', $attribute->slug);
        $this->assertSame('pack', $attribute->type);
        $this->assertTrue($attribute->is_filterable);
        $this->assertTrue($attribute->is_variant_defining);
    }

    public function test_admin_can_update_attribute(): void
    {
        $attribute = Attribute::factory()->create(['name' => 'Old Attribute', 'slug' => 'old-attribute']);

        $response = $this->actingAs($this->admin)->put('/admin/attributes/'.$attribute->id, [
            'name' => 'Volume',
            'slug' => 'volume',
            'type' => 'volume',
            'display_order' => 2,
            'status' => 1,
            'is_filterable' => 1,
            'is_variant_defining' => 1,
        ]);

        $response->assertRedirect(route('admin.attributes.index'));

        $attribute->refresh();

        $this->assertSame('Volume', $attribute->name);
        $this->assertSame('volume', $attribute->slug);
        $this->assertSame('volume', $attribute->type);
    }

    public function test_admin_can_soft_delete_and_restore_attribute(): void
    {
        $attribute = Attribute::factory()->create();

        $this->actingAs($this->admin)->delete('/admin/attributes/'.$attribute->id)
            ->assertRedirect(route('admin.attributes.index'));

        $this->assertSoftDeleted('attributes', ['id' => $attribute->id]);

        $this->actingAs($this->admin)->patch('/admin/attributes/'.$attribute->id.'/restore')
            ->assertRedirect(route('admin.attributes.index', ['trashed' => 'with']));

        $this->assertNotSoftDeleted('attributes', ['id' => $attribute->id]);
    }

    public function test_admin_can_bulk_update_status_delete_and_restore(): void
    {
        $attributes = Attribute::factory()->count(2)->create(['status' => true]);
        $ids = $attributes->pluck('id')->all();

        $this->actingAs($this->admin)->post('/admin/attributes/bulk-action', [
            'ids' => $ids,
            'action' => 'deactivate',
        ])->assertRedirect(route('admin.attributes.index'));

        $this->assertSame(0, Attribute::query()->where('status', true)->count());

        $this->actingAs($this->admin)->post('/admin/attributes/bulk-action', [
            'ids' => $ids,
            'action' => 'delete',
        ])->assertRedirect(route('admin.attributes.index'));

        $this->assertSame(2, Attribute::onlyTrashed()->count());

        $this->actingAs($this->admin)->post('/admin/attributes/bulk-action', [
            'ids' => $ids,
            'action' => 'restore',
        ])->assertRedirect(route('admin.attributes.index'));

        $this->assertSame(0, Attribute::onlyTrashed()->count());
    }

    public function test_attribute_validation_rejects_duplicate_name_and_invalid_type(): void
    {
        Attribute::factory()->create(['name' => 'Weight', 'slug' => 'weight']);

        $response = $this->actingAs($this->admin)->post('/admin/attributes', [
            'name' => 'Weight',
            'slug' => 'weight',
            'type' => 'invalid-type',
        ]);

        $response->assertSessionHasErrors(['name', 'slug', 'type']);
    }

    public function test_attribute_service_rejects_invalid_type_on_create(): void
    {
        $service = app(AttributeService::class);

        $this->expectException(InvalidArgumentException::class);

        $service->create([
            'name' => 'Invalid Attribute',
            'type' => 'invalid-type',
            'display_order' => 1,
            'status' => 1,
            'is_filterable' => 1,
            'is_variant_defining' => 0,
        ]);
    }

    public function test_attribute_service_rejects_invalid_type_on_update(): void
    {
        $attribute = Attribute::factory()->create(['type' => Attribute::TYPES[0]]);
        $service = app(AttributeService::class);

        $this->expectException(InvalidArgumentException::class);

        $service->update($attribute, [
            'name' => 'Updated Attribute',
            'type' => 'invalid-type',
            'display_order' => 1,
            'status' => 1,
            'is_filterable' => 1,
            'is_variant_defining' => 0,
        ]);
    }

    public function test_attribute_routes_require_authentication_and_authorization_middleware(): void
    {
        foreach ([
            'admin.attributes.index',
            'admin.attributes.create',
            'admin.attributes.store',
            'admin.attributes.show',
            'admin.attributes.edit',
            'admin.attributes.update',
            'admin.attributes.destroy',
            'admin.attributes.restore',
            'admin.attributes.bulk-action',
        ] as $routeName) {
            $middleware = Route::getRoutes()->getByName($routeName)->gatherMiddleware();

            $this->assertContains('auth', $middleware);
            $this->assertContains('can:manage-attributes', $middleware);
        }
    }

    public function test_unauthorized_user_cannot_access_attribute_administration(): void
    {
        $user = User::factory()->create(['email' => 'customer@example.com']);
        $attribute = Attribute::factory()->create();

        $this->actingAs($user)->get('/admin/attributes')->assertForbidden();
        $this->actingAs($user)->get('/admin/attributes/create')->assertForbidden();
        $this->actingAs($user)->post('/admin/attributes', [
            'name' => 'Unauthorized Attribute',
        ])->assertForbidden();
        $this->actingAs($user)->delete('/admin/attributes/'.$attribute->id)->assertForbidden();
    }

    public function test_attribute_service_blocks_deletion_when_future_usage_exists(): void
    {
        $attribute = Attribute::factory()->create();
        $repository = Mockery::mock(AttributeRepositoryInterface::class);

        $repository->shouldReceive('idsInUse')
            ->once()
            ->with([$attribute->id])
            ->andReturn([$attribute->id]);

        $service = new AttributeService($repository, new SlugService);

        $this->expectException(InvalidArgumentException::class);

        $service->delete($attribute);
    }
}
