<?php

namespace Tests\Feature;

use App\Models\DeliverySlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliverySlotManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['grihasthikart.admin_emails' => ['admin@example.com']]);
        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
    }

    public function test_delivery_slot_crud_works(): void
    {
        $this->actingAs($this->admin)->post(route('admin.delivery-slots.store'), [
            'name' => '7-9 AM',
            'start_time' => '07:00',
            'end_time' => '09:00',
            'display_label' => '7-9 AM',
            'status' => 1,
            'display_order' => 1,
        ])->assertRedirect(route('admin.delivery-slots.index'));

        $slot = DeliverySlot::query()->firstOrFail();
        $this->assertSame('7-9 AM', $slot->name);

        $this->actingAs($this->admin)->put(route('admin.delivery-slots.update', $slot), [
            'name' => '7-10 AM',
            'start_time' => '07:00',
            'end_time' => '10:00',
            'display_label' => '7-10 AM',
            'status' => 1,
            'display_order' => 2,
        ])->assertRedirect(route('admin.delivery-slots.index'));

        $this->assertSame('7-10 AM', $slot->fresh()->name);

        $this->actingAs($this->admin)->delete(route('admin.delivery-slots.destroy', $slot))->assertRedirect();
        $this->assertSoftDeleted('delivery_slots', ['id' => $slot->id]);

        $this->actingAs($this->admin)->patch(route('admin.delivery-slots.restore', $slot->id))->assertRedirect();
        $this->assertNotSoftDeleted('delivery_slots', ['id' => $slot->id]);
    }

    public function test_overlapping_active_slots_are_rejected(): void
    {
        DeliverySlot::factory()->create([
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => true,
        ]);

        $this->actingAs($this->admin)->post(route('admin.delivery-slots.store'), [
            'name' => '10-12',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'status' => 1,
        ])->assertSessionHasErrors('slot');
    }

    public function test_inactive_slots_do_not_appear_on_checkout_page(): void
    {
        $active = DeliverySlot::factory()->create([
            'name' => '9-11 AM',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'display_label' => '9-11 AM',
            'status' => true,
        ]);
        DeliverySlot::factory()->inactive()->create([
            'name' => 'Midnight',
            'start_time' => '00:00',
            'end_time' => '01:00',
            'display_label' => 'Midnight',
        ]);

        $response = $this->get(route('checkout.show'));

        $response->assertRedirect(route('cart.show'));
        $this->assertTrue($active->status);
    }

    public function test_delivery_slot_routes_require_authorization(): void
    {
        $user = User::factory()->create(['email' => 'customer@example.com']);

        $this->actingAs($user)->get(route('admin.delivery-slots.index'))->assertForbidden();
    }
}
