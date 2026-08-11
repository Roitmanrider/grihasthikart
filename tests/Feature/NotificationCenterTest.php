<?php

namespace Tests\Feature;

use App\Domains\Notification\Services\NotificationService;
use App\Domains\Setting\Services\BusinessSettingService;
use App\Domains\Storefront\Services\StorefrontAccessService;
use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Inventory;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\BusinessSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);
        $this->seed(BusinessSettingSeeder::class);
        app(BusinessSettingService::class)->set('storefront.access_mode', StorefrontAccessService::PUBLIC_STOREFRONT);
        app(BusinessSettingService::class)->set('storefront.allow_guest_checkout', true);
        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
    }

    public function test_notification_created_for_new_order(): void
    {
        [, $variant] = $this->purchasableVariant();

        $this->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->post(route('checkout.place'), $this->checkoutPayload())->assertRedirect();

        $order = Order::query()->firstOrFail();

        $this->assertDatabaseHas('notifications', [
            'audience' => Notification::AUDIENCE_ADMIN,
            'type' => 'order.placed',
            'notifiable_type' => Order::class,
            'notifiable_id' => $order->id,
        ]);
    }

    public function test_customer_cancellation_notifies_admin(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'order_status' => 'placed',
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->patch(route('customer.orders.cancel', $order->order_number), ['reason' => 'Ordered by mistake'])
            ->assertRedirect(route('customer.orders.show', $order->order_number));

        $this->assertDatabaseHas('notifications', [
            'audience' => Notification::AUDIENCE_ADMIN,
            'type' => 'order.cancelled_by_customer',
            'notifiable_id' => $order->id,
        ]);
    }

    public function test_admin_cancellation_notifies_customer_with_reason(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'order_status' => 'confirmed',
        ]);

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.update-status', $order), [
                'order_status' => 'cancelled_by_admin',
                'admin_notes' => 'Stock unavailable',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertDatabaseHas('notifications', [
            'audience' => Notification::AUDIENCE_CUSTOMER,
            'customer_id' => $customer->id,
            'type' => 'order.cancelled_by_admin',
            'notifiable_id' => $order->id,
        ]);
        $this->assertStringContainsString('Stock unavailable', Notification::query()->where('customer_id', $customer->id)->firstOrFail()->message);
    }

    public function test_order_status_change_notifies_customer(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'order_status' => 'placed',
        ]);

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.update-status', $order), ['order_status' => 'confirmed'])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertDatabaseHas('notifications', [
            'audience' => Notification::AUDIENCE_CUSTOMER,
            'customer_id' => $customer->id,
            'type' => 'order.status_changed',
            'notifiable_id' => $order->id,
        ]);
    }

    public function test_notification_counts_show_unread(): void
    {
        $customer = Customer::factory()->create();
        Notification::query()->create([
            'audience' => Notification::AUDIENCE_ADMIN,
            'type' => 'test.admin',
            'title' => 'Admin alert',
        ]);
        Notification::query()->create([
            'audience' => Notification::AUDIENCE_CUSTOMER,
            'customer_id' => $customer->id,
            'type' => 'test.customer',
            'title' => 'Customer alert',
        ]);

        $service = app(NotificationService::class);

        $this->assertSame(1, $service->adminUnreadCount());
        $this->assertSame(1, $service->customerUnreadCount($customer));

        $this->actingAs($this->admin)
            ->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee('Admin alert');

        $this->withSession(['customer_id' => $customer->id])
            ->get(route('customer.notifications.index'))
            ->assertOk()
            ->assertSee('Customer alert');

        $this->assertSame(0, $service->customerUnreadCount($customer));
    }

    public function test_mark_one_notification_as_read(): void
    {
        $notification = Notification::query()->create([
            'audience' => Notification::AUDIENCE_ADMIN,
            'type' => 'test.admin',
            'title' => 'Admin alert',
        ]);

        $this->actingAs($this->admin)
            ->patch(route('admin.notifications.read', $notification))
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_mark_all_notifications_as_read(): void
    {
        $customer = Customer::factory()->create();
        Notification::query()->create([
            'audience' => Notification::AUDIENCE_CUSTOMER,
            'customer_id' => $customer->id,
            'type' => 'test.one',
            'title' => 'One',
        ]);
        Notification::query()->create([
            'audience' => Notification::AUDIENCE_CUSTOMER,
            'customer_id' => $customer->id,
            'type' => 'test.two',
            'title' => 'Two',
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->patch(route('customer.notifications.read-all'))
            ->assertRedirect();

        $this->assertSame(0, Notification::query()->forCustomer($customer)->unread()->count());
    }

    public function test_customer_cannot_access_another_customers_notification(): void
    {
        $customer = Customer::factory()->create();
        $other = Customer::factory()->create();
        $notification = Notification::query()->create([
            'audience' => Notification::AUDIENCE_CUSTOMER,
            'customer_id' => $other->id,
            'type' => 'test.other',
            'title' => 'Private alert',
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->patch(route('customer.notifications.read', $notification))
            ->assertNotFound();
    }

    public function test_customer_notifications_retain_latest_twenty_one_and_paginate_ten(): void
    {
        $customer = Customer::factory()->create();
        $service = app(NotificationService::class);

        for ($i = 1; $i <= 22; $i++) {
            $this->travelTo(now()->addSeconds($i));
            $address = CustomerAddress::factory()->create([
                'customer_id' => $customer->id,
                'label' => 'Address '.$i,
            ]);

            $service->notifyCustomerAddressApprovalChanged($address, true);
        }
        $this->travelBack();

        $this->assertSame(21, Notification::query()->forCustomer($customer)->count());
        $this->assertDatabaseMissing('notifications', [
            'audience' => Notification::AUDIENCE_CUSTOMER,
            'customer_id' => $customer->id,
            'message' => 'Your Address 1 delivery address has been approved.',
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->get(route('customer.notifications.index'))
            ->assertOk()
            ->assertSee('Your Address 22 delivery address has been approved.')
            ->assertDontSee('Your Address 11 delivery address has been approved.');
    }

    public function test_opening_customer_notification_page_marks_customer_notifications_read_only(): void
    {
        $customer = Customer::factory()->create();
        $other = Customer::factory()->create();
        Notification::query()->create([
            'audience' => Notification::AUDIENCE_CUSTOMER,
            'customer_id' => $customer->id,
            'type' => 'test.customer',
            'title' => 'Customer alert',
        ]);
        $otherNotification = Notification::query()->create([
            'audience' => Notification::AUDIENCE_CUSTOMER,
            'customer_id' => $other->id,
            'type' => 'test.other',
            'title' => 'Other alert',
        ]);
        $adminNotification = Notification::query()->create([
            'audience' => Notification::AUDIENCE_ADMIN,
            'type' => 'test.admin',
            'title' => 'Admin alert',
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->get(route('customer.notifications.index'))
            ->assertOk()
            ->assertSee('Read')
            ->assertSee('text-bg-secondary', false);

        $this->assertSame(0, Notification::query()->forCustomer($customer)->unread()->count());
        $this->assertNull($otherNotification->fresh()->read_at);
        $this->assertNull($adminNotification->fresh()->read_at);
    }

    public function test_opening_actionable_notification_marks_it_read_before_redirect(): void
    {
        $customer = Customer::factory()->create();
        $notification = Notification::query()->create([
            'audience' => Notification::AUDIENCE_CUSTOMER,
            'customer_id' => $customer->id,
            'type' => 'test.customer',
            'title' => 'Customer alert',
            'action_url' => route('customer.dashboard'),
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->get(route('customer.notifications.open', $notification))
            ->assertRedirect(route('customer.dashboard'));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_guest_blocked_from_notification_centers(): void
    {
        $this->get(route('admin.notifications.index'))
            ->assertRedirect(route('admin.login'));

        $this->get(route('customer.notifications.index'))
            ->assertRedirect(route('customer.login'));
    }

    private function checkoutPayload(): array
    {
        return [
            'customer_name' => 'Rohit Kumar',
            'customer_mobile' => '9876543210',
            'customer_email' => 'rohit@example.com',
            'delivery_address_line1' => 'House 12',
            'delivery_city' => 'Patna',
            'delivery_state' => 'Bihar',
            'delivery_pincode' => '800001',
            'payment_method' => 'cod',
        ];
    }

    private function purchasableVariant(): array
    {
        BusinessSetting::query()->where('group', 'payment')->where('key', 'cod_enabled')->update(['value' => '1']);

        $product = Product::factory()->create(['status' => true]);
        $variant = ProductVariant::factory()->default()->create([
            'product_id' => $product->id,
            'status' => true,
            'selling_price' => 68,
            'mrp' => 75,
        ]);
        $product->update(['default_variant_id' => $variant->id]);
        Inventory::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 10,
            'reserved_quantity' => 0,
            'damaged_quantity' => 0,
            'low_stock_threshold' => null,
        ]);

        return [$product, $variant];
    }
}
