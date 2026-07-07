<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);

        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
    }

    public function test_admin_can_view_paginated_categories_with_search_filters(): void
    {
        Category::factory()->create(['name' => 'Fresh Fruits', 'slug' => 'fresh-fruits', 'status' => true]);
        Category::factory()->create(['name' => 'Dishwash', 'slug' => 'dishwash', 'status' => false]);

        $response = $this->actingAs($this->admin)->get('/admin/categories?search=Fruits&status=1');

        $response->assertOk();
        $response->assertSee('Fresh Fruits');
        $response->assertViewHas('categories', function ($categories) {
            return $categories->pluck('name')->contains('Fresh Fruits')
                && ! $categories->pluck('name')->contains('Dishwash');
        });
    }

    public function test_admin_can_create_category_with_generated_slug_seo_and_image(): void
    {
        Storage::fake('uploads');

        $response = $this->actingAs($this->admin)->post('/admin/categories', [
            'name' => 'Fresh Vegetables',
            'description' => 'Daily fresh vegetables.',
            'image' => UploadedFile::fake()->image('vegetables.jpg'),
            'meta_title' => 'Fresh Vegetables Online',
            'meta_description' => 'Buy fresh vegetables online.',
            'meta_keywords' => 'fresh, vegetables',
            'display_order' => 1,
            'status' => 1,
            'is_featured' => 1,
            'show_in_menu' => 1,
            'show_on_homepage' => 1,
        ]);

        $response->assertRedirect(route('admin.categories.index'));

        $category = Category::query()->where('name', 'Fresh Vegetables')->firstOrFail();

        $this->assertSame('fresh-vegetables', $category->slug);
        $this->assertSame('Fresh Vegetables Online', $category->meta_title);
        $this->assertTrue($category->is_featured);
        $this->assertNotNull($category->image);
        Storage::disk('uploads')->assertExists($category->image);
    }

    public function test_admin_can_update_category_and_prevent_circular_parent_relationships(): void
    {
        $parent = Category::factory()->create(['name' => 'Staples']);
        $child = Category::factory()->create(['name' => 'Rice', 'parent_id' => $parent->id]);

        $response = $this->actingAs($this->admin)->put('/admin/categories/'.$parent->id, [
            'name' => 'Staples',
            'slug' => 'staples',
            'parent_id' => $child->id,
            'display_order' => 1,
            'status' => 1,
            'is_featured' => 0,
            'show_in_menu' => 1,
            'show_on_homepage' => 0,
        ]);

        $response->assertSessionHasErrors('parent_id');
        $this->assertNull($parent->fresh()->parent_id);
    }

    public function test_admin_can_soft_delete_and_restore_category(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin)->delete('/admin/categories/'.$category->id)
            ->assertRedirect(route('admin.categories.index'));

        $this->assertSoftDeleted('categories', ['id' => $category->id]);

        $this->actingAs($this->admin)->patch('/admin/categories/'.$category->id.'/restore')
            ->assertRedirect(route('admin.categories.index', ['trashed' => 'with']));

        $this->assertNotSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_admin_cannot_delete_parent_category_with_children(): void
    {
        $parent = Category::factory()->create(['name' => 'Staples']);
        Category::factory()->create(['name' => 'Rice', 'parent_id' => $parent->id]);

        $this->actingAs($this->admin)
            ->from('/admin/categories')
            ->delete('/admin/categories/'.$parent->id)
            ->assertRedirect('/admin/categories')
            ->assertSessionHasErrors('category');

        $this->assertNotSoftDeleted('categories', ['id' => $parent->id]);
    }

    public function test_admin_cannot_create_active_category_under_inactive_parent(): void
    {
        $parent = Category::factory()->create(['status' => false]);

        $this->actingAs($this->admin)
            ->from('/admin/categories/create')
            ->post('/admin/categories', [
                'name' => 'Basmati Rice',
                'parent_id' => $parent->id,
                'display_order' => 1,
                'status' => 1,
                'is_featured' => 0,
                'show_in_menu' => 1,
                'show_on_homepage' => 0,
            ])
            ->assertRedirect('/admin/categories/create')
            ->assertSessionHasErrors('parent_id');

        $this->assertDatabaseMissing('categories', ['name' => 'Basmati Rice']);
    }

    public function test_admin_cannot_deactivate_parent_category_with_active_children(): void
    {
        $parent = Category::factory()->create(['status' => true]);
        Category::factory()->create(['parent_id' => $parent->id, 'status' => true]);

        $this->actingAs($this->admin)
            ->from('/admin/categories')
            ->post('/admin/categories/bulk-action', [
                'ids' => [$parent->id],
                'action' => 'deactivate',
            ])
            ->assertRedirect('/admin/categories')
            ->assertSessionHasErrors('category');

        $this->assertTrue($parent->fresh()->status);
    }

    public function test_admin_can_bulk_update_status_delete_and_restore(): void
    {
        $categories = Category::factory()->count(2)->create(['status' => true]);
        $ids = $categories->pluck('id')->all();

        $this->actingAs($this->admin)->post('/admin/categories/bulk-action', [
            'ids' => $ids,
            'action' => 'deactivate',
        ])->assertRedirect(route('admin.categories.index'));

        $this->assertSame(0, Category::query()->where('status', true)->count());

        $this->actingAs($this->admin)->post('/admin/categories/bulk-action', [
            'ids' => $ids,
            'action' => 'delete',
        ])->assertRedirect(route('admin.categories.index'));

        $this->assertSame(2, Category::onlyTrashed()->count());

        $this->actingAs($this->admin)->post('/admin/categories/bulk-action', [
            'ids' => $ids,
            'action' => 'restore',
        ])->assertRedirect(route('admin.categories.index'));

        $this->assertSame(0, Category::onlyTrashed()->count());
    }

    public function test_category_validation_rejects_missing_name_and_invalid_image(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/categories', [
            'name' => '',
            'image' => UploadedFile::fake()->create('category.pdf', 10, 'application/pdf'),
        ]);

        $response->assertSessionHasErrors(['name', 'image']);
    }

    public function test_category_routes_require_authentication_and_authorization_middleware(): void
    {
        foreach ([
            'admin.categories.index',
            'admin.categories.create',
            'admin.categories.store',
            'admin.categories.show',
            'admin.categories.edit',
            'admin.categories.update',
            'admin.categories.destroy',
            'admin.categories.restore',
            'admin.categories.bulk-action',
        ] as $routeName) {
            $middleware = Route::getRoutes()->getByName($routeName)->gatherMiddleware();

            $this->assertContains('auth', $middleware);
            $this->assertContains('can:manage-categories', $middleware);
        }
    }

    public function test_unauthorized_user_cannot_access_category_administration(): void
    {
        $user = User::factory()->create(['email' => 'customer@example.com']);
        $category = Category::factory()->create();

        $this->actingAs($user)->get('/admin/categories')->assertForbidden();
        $this->actingAs($user)->get('/admin/categories/create')->assertForbidden();
        $this->actingAs($user)->post('/admin/categories', [
            'name' => 'Unauthorized Category',
        ])->assertForbidden();
        $this->actingAs($user)->delete('/admin/categories/'.$category->id)->assertForbidden();
    }
}
