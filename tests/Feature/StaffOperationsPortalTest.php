<?php

namespace Tests\Feature;

use App\Domains\Staff\Services\DeliveryOtpAccessService;
use App\Domains\Staff\Services\DeliveryWorkflowService;
use App\Domains\Staff\Services\OrderStaffTaskService;
use App\Domains\Staff\Services\StaffNotificationService;
use App\Domains\Staff\Services\StaffPermissionService;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\DeliveryAttempt;
use App\Models\DeliveryEvent;
use App\Models\DeliveryOtp;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderStaffAssignment;
use App\Models\StaffNotification;
use App\Models\StockLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class StaffOperationsPortalTest extends TestCase
{
    use RefreshDatabase;

    private StockLocation $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = StockLocation::factory()->default()->create();
    }

    public function test_multi_role_permissions_union_custom_allow_and_denied_override(): void
    {
        $user = User::factory()->create([
            'assigned_store_id' => $this->store->id,
            'staff_roles' => ['DELIVERY_AGENT', 'CART_FOLLOW_UP_EMPLOYEE'],
            'additional_permissions' => ['inventory.view'],
            'denied_permissions' => ['delivery.mark_failed'],
            'staff_active' => true,
        ]);

        $service = app(StaffPermissionService::class);

        $this->assertTrue($service->has($user, 'delivery.start'));
        $this->assertTrue($service->has($user, 'cart_followup.manage'));
        $this->assertTrue($service->has($user, 'inventory.view'));
        $this->assertFalse($service->has($user, 'delivery.mark_failed'));
    }

    public function test_staff_login_and_admin_boundary_are_separate(): void
    {
        $staff = User::factory()->create([
            'assigned_store_id' => $this->store->id,
            'staff_roles' => ['PICKER_PACKER'],
            'staff_active' => true,
        ]);

        $this->post(route('staff.login.submit'), [
            'email' => $staff->email,
            'password' => 'password',
        ])->assertRedirect(route('staff.dashboard'));

        $this->get(route('staff.dashboard'))->assertOk()->assertSee('Staff Dashboard');
        $this->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_picking_and_packing_can_be_completed_by_same_multi_role_employee(): void
    {
        $employee = $this->staff(['PICKER_PACKER']);
        $manager = $this->staff(['STORE_MANAGER']);
        $order = $this->order(['order_status' => 'confirmed']);
        $tasks = app(OrderStaffTaskService::class);

        $picking = $tasks->assign($order, OrderStaffAssignment::TASK_PICKING, $employee, $manager);
        $tasks->start($picking, $employee);
        $tasks->complete($picking, $employee);

        $packing = $tasks->assign($order->fresh(), OrderStaffAssignment::TASK_PACKING, $employee, $manager);
        $tasks->start($packing, $employee);
        $tasks->complete($packing, $employee);

        $this->assertSame('COMPLETED', $picking->fresh()->status);
        $this->assertSame('COMPLETED', $packing->fresh()->status);
        $this->assertSame('packed', $order->fresh()->order_status);
    }

    public function test_store_scope_blocks_other_store_task_access(): void
    {
        $otherStore = StockLocation::factory()->create();
        $employee = User::factory()->create([
            'assigned_store_id' => $this->store->id,
            'staff_roles' => ['PICKER_PACKER'],
            'staff_active' => true,
        ]);
        $assignment = OrderStaffAssignment::query()->create([
            'order_id' => $this->order(['stock_location_id' => $otherStore->id])->id,
            'stock_location_id' => $otherStore->id,
            'task_type' => OrderStaffAssignment::TASK_PICKING,
            'assigned_user_id' => $employee->id,
            'status' => 'ASSIGNED',
        ]);

        $this->actingAs($employee)
            ->patch(route('staff.assignments.start', $assignment))
            ->assertForbidden();
    }

    public function test_delivery_otp_customer_visibility_and_staff_verification(): void
    {
        $agent = $this->staff(['DELIVERY_AGENT']);
        $manager = $this->staff(['STORE_MANAGER']);
        $customer = Customer::factory()->create(['assigned_store_id' => $this->store->id]);
        CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'is_default' => true,
            'latitude' => 22.5726000,
            'longitude' => 88.3639000,
            'geofence_radius_meters' => 200,
        ]);
        $order = $this->order(['customer_id' => $customer->id, 'order_status' => 'packed']);
        $assignment = app(OrderStaffTaskService::class)->assign($order, OrderStaffAssignment::TASK_DELIVERY, $agent, $manager);
        $attempt = app(DeliveryWorkflowService::class)->startDelivery($assignment, $agent);
        $credential = DeliveryOtp::query()->where('delivery_attempt_id', $attempt->id)->firstOrFail();
        $otp = Crypt::decryptString($credential->otp_ciphertext);

        $this->withSession(['customer_id' => $customer->id])
            ->get(route('customer.orders.show', $order->order_number))
            ->assertOk()
            ->assertSee($otp);

        app(DeliveryWorkflowService::class)->verifyOtp($attempt, $agent, $otp, [
            'latitude' => 22.5727000,
            'longitude' => 88.3640000,
            'accuracy_meters' => 12,
        ]);

        $this->assertSame('delivered', $order->fresh()->order_status);
        $this->assertNotNull($credential->fresh()->used_at);
        $this->assertDatabaseHas('delivery_events', [
            'delivery_attempt_id' => $attempt->id,
            'event_type' => 'DELIVERED',
            'otp_verified' => true,
            'geofence_result' => 'INSIDE',
        ]);
    }

    public function test_delivery_otp_plaintext_is_not_stored_in_permanent_notifications_or_events(): void
    {
        $agent = $this->staff(['DELIVERY_AGENT']);
        $attempt = $this->deliveryAttempt($agent);
        $credential = DeliveryOtp::query()->where('delivery_attempt_id', $attempt->id)->firstOrFail();
        $otp = Crypt::decryptString($credential->otp_ciphertext);

        $notification = Notification::query()
            ->where('type', 'delivery_otp')
            ->where('customer_id', $attempt->order->customer_id)
            ->firstOrFail();

        $this->assertStringNotContainsString($otp, $notification->title);
        $this->assertStringNotContainsString($otp, (string) $notification->message);
        $this->assertStringNotContainsString($otp, json_encode($notification->data));
        $this->assertSame(['delivery_otp_id' => $credential->id], $notification->data);
        $this->assertFalse(
            DeliveryEvent::query()->get()->contains(
                fn (DeliveryEvent $event) => str_contains(json_encode($event->getAttributes()), $otp)
            )
        );
    }

    public function test_customer_delivery_otp_visibility_requires_customer_ownership_and_active_lifecycle(): void
    {
        $agent = $this->staff(['DELIVERY_AGENT']);
        $attempt = $this->deliveryAttempt($agent);
        $order = $attempt->order;
        $customer = $order->customer;
        $otherCustomer = Customer::factory()->create(['assigned_store_id' => $this->store->id]);
        $credential = DeliveryOtp::query()->where('delivery_attempt_id', $attempt->id)->firstOrFail();
        $otp = Crypt::decryptString($credential->otp_ciphertext);

        $access = app(DeliveryOtpAccessService::class);
        $notification = Notification::query()
            ->where('type', 'delivery_otp')
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        $this->assertNull($access->activeCodeForCustomerOrder($otherCustomer, $order));
        $this->assertNull($access->activeCodeForCustomerNotification($otherCustomer, $notification));
        $this->assertSame($otp, $access->activeCodeForCustomerOrder($customer, $order));
        $this->assertSame($otp, $access->activeCodeForCustomerNotification($customer, $notification));

        $credential->update(['used_at' => now(), 'invalidated_at' => now()]);

        $this->assertNull($access->activeCodeForCustomerOrder($customer, $order));
        $this->assertNull($access->activeCodeForCustomerNotification($customer, $notification));
    }

    public function test_staff_screens_never_render_customer_delivery_otp(): void
    {
        $agent = $this->staff(['DELIVERY_AGENT']);
        $attempt = $this->deliveryAttempt($agent);
        $otp = Crypt::decryptString(DeliveryOtp::query()->where('delivery_attempt_id', $attempt->id)->firstOrFail()->otp_ciphertext);

        $this->actingAs($agent)
            ->get(route('staff.deliveries.index'))
            ->assertOk()
            ->assertSee('Enter Delivery OTP')
            ->assertDontSee($otp);
    }

    public function test_delivery_otp_failed_attempts_are_limited_and_invalidate_active_credential(): void
    {
        $agent = $this->staff(['DELIVERY_AGENT']);
        $attempt = $this->deliveryAttempt($agent);

        for ($i = 0; $i < 4; $i++) {
            try {
                app(DeliveryWorkflowService::class)->verifyOtp($attempt, $agent, '000000');
                $this->fail('Invalid OTP should not verify.');
            } catch (\InvalidArgumentException $exception) {
                $this->assertSame('Invalid delivery OTP.', $exception->getMessage());
            }
        }

        try {
            app(DeliveryWorkflowService::class)->verifyOtp($attempt, $agent, '000000');
            $this->fail('OTP should be blocked after excessive failures.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertSame('Too many invalid delivery OTP attempts. Request delivery override or reschedule.', $exception->getMessage());
        }

        $credential = DeliveryOtp::query()->where('delivery_attempt_id', $attempt->id)->firstOrFail();
        $this->assertSame(5, (int) $credential->failed_attempt_count);
        $this->assertNotNull($credential->invalidated_at);
    }

    public function test_reschedule_invalidates_old_otp_and_new_attempt_receives_fresh_otp(): void
    {
        $agent = $this->staff(['DELIVERY_AGENT']);
        $attempt = $this->deliveryAttempt($agent);
        $oldCredential = DeliveryOtp::query()->where('delivery_attempt_id', $attempt->id)->firstOrFail();

        app(DeliveryWorkflowService::class)->recordFailure($attempt, $agent, 'RESCHEDULE_REQUESTED', 'CUSTOMER_REQUESTED', 'Tomorrow');

        $newAttempt = app(DeliveryWorkflowService::class)->startDelivery($attempt->assignment, $agent);
        $newCredential = DeliveryOtp::query()->where('delivery_attempt_id', $newAttempt->id)->firstOrFail();

        $this->assertNotNull($oldCredential->fresh()->invalidated_at);
        $this->assertNotSame($oldCredential->id, $newCredential->id);
        $this->assertNull($newCredential->invalidated_at);
        $this->assertSame(2, (int) $newAttempt->attempt_number);
    }

    public function test_delivery_gps_far_flags_review_but_does_not_block_valid_otp(): void
    {
        $agent = $this->staff(['DELIVERY_AGENT']);
        $attempt = $this->deliveryAttempt($agent);
        $otp = Crypt::decryptString(DeliveryOtp::query()->where('delivery_attempt_id', $attempt->id)->firstOrFail()->otp_ciphertext);

        app(DeliveryWorkflowService::class)->verifyOtp($attempt, $agent, $otp, [
            'latitude' => 23.0000000,
            'longitude' => 89.0000000,
        ]);

        $event = DeliveryEvent::query()->where('delivery_attempt_id', $attempt->id)->where('event_type', 'DELIVERED')->firstOrFail();
        $this->assertSame('OUTSIDE', $event->geofence_result);
        $this->assertTrue($event->manager_review_required);
    }

    public function test_maker_checker_blocks_self_approval_and_allows_independent_manager(): void
    {
        $agentManager = $this->staff(['DELIVERY_AGENT', 'STORE_MANAGER']);
        $checker = $this->staff(['STORE_MANAGER']);
        $attempt = $this->deliveryAttempt($agentManager);

        $approval = app(DeliveryWorkflowService::class)->requestApproval($attempt, $agentManager, 'RETURN_TO_STORE', 'CUSTOMER_REFUSED', 'Package returned');

        try {
            app(DeliveryWorkflowService::class)->decideApproval($approval, $agentManager, true);
            $this->fail('Self approval should be denied.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertSame('Maker cannot approve their own request.', $exception->getMessage());
        }

        app(DeliveryWorkflowService::class)->decideApproval($approval->fresh(), $checker, true);
        $this->assertSame('APPROVED', $approval->fresh()->status);
        $this->assertSame('RETURNED_TO_STORE_CONFIRMED', $attempt->fresh()->status);
    }

    public function test_one_person_store_request_remains_available_to_super_admin_fallback(): void
    {
        $agentManager = $this->staff(['DELIVERY_AGENT', 'STORE_MANAGER']);
        $superAdmin = User::factory()->create(['role' => 'SUPER_ADMIN', 'staff_active' => true]);
        $attempt = $this->deliveryAttempt($agentManager);
        $approval = app(DeliveryWorkflowService::class)->requestApproval($attempt, $agentManager, 'RETURN_TO_STORE', 'CUSTOMER_REFUSED', 'Package returned');

        $this->actingAs($agentManager)
            ->get(route('staff.approvals.index'))
            ->assertOk()
            ->assertDontSee('CUSTOMER_REFUSED');

        $this->actingAs($superAdmin)
            ->get(route('staff.approvals.index'))
            ->assertOk()
            ->assertSee('CUSTOMER_REFUSED');

        app(DeliveryWorkflowService::class)->decideApproval($approval, $superAdmin, true);
        $this->assertSame('APPROVED', $approval->fresh()->status);
    }

    public function test_cross_store_approval_and_assignment_permissions_are_enforced(): void
    {
        $otherStore = StockLocation::factory()->create();
        $storeOneManager = $this->staff(['STORE_MANAGER']);
        $storeTwoAgent = User::factory()->create([
            'assigned_store_id' => $otherStore->id,
            'staff_roles' => ['DELIVERY_AGENT'],
            'staff_active' => true,
        ]);
        $storeTwoOrder = Order::factory()->create([
            'stock_location_id' => $otherStore->id,
            'customer_id' => Customer::factory()->create(['assigned_store_id' => $otherStore->id])->id,
            'order_status' => 'packed',
        ]);
        $storeTwoAssignment = app(OrderStaffTaskService::class)->assign($storeTwoOrder, OrderStaffAssignment::TASK_DELIVERY, $storeTwoAgent, User::factory()->create(['role' => 'SUPER_ADMIN', 'staff_active' => true]));
        $attempt = app(DeliveryWorkflowService::class)->startDelivery($storeTwoAssignment, $storeTwoAgent);
        $approval = app(DeliveryWorkflowService::class)->requestApproval($attempt, $storeTwoAgent, 'RETURN_TO_STORE', 'CUSTOMER_REFUSED', 'Package returned');

        $this->actingAs($storeOneManager)
            ->patch(route('staff.approvals.decide', $approval), ['decision' => 'approve'])
            ->assertForbidden();

        $picker = $this->staff(['PICKER_PACKER']);
        $order = $this->order(['order_status' => 'confirmed']);

        $this->actingAs($picker)
            ->post(route('staff.assignments.assign'), [
                'order_id' => $order->id,
                'task_type' => OrderStaffAssignment::TASK_PICKING,
                'assigned_user_id' => $picker->id,
            ])
            ->assertForbidden();
    }

    public function test_staff_notification_counts_and_workstream_read_behavior(): void
    {
        $staff = $this->staff(['DELIVERY_AGENT', 'CART_FOLLOW_UP_EMPLOYEE']);
        $service = app(StaffNotificationService::class);

        $service->notify($staff, 'delivery', 'delivery_assigned', 'Delivery assigned', storeId: $this->store->id);
        $service->notify($staff, 'cart_followup', 'cart_assigned', 'Cart assigned', storeId: $this->store->id);

        $this->assertSame(1, (int) $service->unreadCounts($staff)['delivery']);
        $this->assertSame(1, (int) $service->unreadCounts($staff)['cart_followup']);

        $service->markAllRead($staff, 'delivery');

        $counts = $service->unreadCounts($staff);
        $this->assertFalse(isset($counts['delivery']));
        $this->assertSame(1, (int) $counts['cart_followup']);
        $this->assertSame(2, StaffNotification::query()->count());
    }

    public function test_otp_cleanup_deletes_credentials_but_delivery_event_survives(): void
    {
        $agent = $this->staff(['DELIVERY_AGENT']);
        $attempt = $this->deliveryAttempt($agent);
        DeliveryOtp::query()->where('delivery_attempt_id', $attempt->id)->update([
            'used_at' => now()->subDays(8),
            'invalidated_at' => now()->subDays(8),
        ]);
        DeliveryEvent::query()->create([
            'order_id' => $attempt->order_id,
            'delivery_attempt_id' => $attempt->id,
            'stock_location_id' => $this->store->id,
            'actor_user_id' => $agent->id,
            'event_type' => 'DELIVERED',
            'occurred_at' => now()->subDays(8),
        ]);

        $this->artisan('delivery-otps:cleanup')->assertExitCode(0);

        $this->assertSame(0, DeliveryOtp::query()->count());
        $this->assertSame(2, DeliveryEvent::query()->count());
    }

    public function test_otp_cleanup_does_not_delete_active_old_created_credential(): void
    {
        $agent = $this->staff(['DELIVERY_AGENT']);
        $attempt = $this->deliveryAttempt($agent);
        DeliveryOtp::query()->where('delivery_attempt_id', $attempt->id)->update([
            'generated_at' => now()->subDays(8),
            'created_at' => now()->subDays(8),
            'updated_at' => now()->subDays(8),
            'expires_at' => now()->addHour(),
        ]);

        $this->artisan('delivery-otps:cleanup')->assertExitCode(0);

        $this->assertSame(1, DeliveryOtp::query()->count());
    }

    public function test_otp_override_requires_independent_approval_and_records_override_audit_not_otp_verified(): void
    {
        $agent = $this->staff(['DELIVERY_AGENT']);
        $checker = $this->staff(['STORE_MANAGER']);
        $attempt = $this->deliveryAttempt($agent);

        $approval = app(DeliveryWorkflowService::class)->requestApproval($attempt, $agent, 'DELIVERY_OTP_OVERRIDE', 'CUSTOMER_PHONE_DEAD', 'Customer confirmed identity');
        app(DeliveryWorkflowService::class)->decideApproval($approval, $checker, true);

        $event = DeliveryEvent::query()
            ->where('delivery_attempt_id', $attempt->id)
            ->where('event_type', 'DELIVERED_OTP_OVERRIDE_APPROVED')
            ->firstOrFail();

        $this->assertTrue($event->otp_override_approved);
        $this->assertFalse($event->otp_verified);
        $this->assertSame('delivered', $attempt->order->fresh()->order_status);
    }

    private function staff(array $roles): User
    {
        return User::factory()->create([
            'assigned_store_id' => $this->store->id,
            'staff_roles' => $roles,
            'staff_active' => true,
        ]);
    }

    private function order(array $overrides = []): Order
    {
        return Order::factory()->create(array_merge([
            'stock_location_id' => $this->store->id,
        ], $overrides));
    }

    private function deliveryAttempt(User $agent): DeliveryAttempt
    {
        $manager = $this->staff(['STORE_MANAGER']);
        $customer = Customer::factory()->create(['assigned_store_id' => $this->store->id]);
        CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'is_default' => true,
            'latitude' => 22.5726000,
            'longitude' => 88.3639000,
            'geofence_radius_meters' => 150,
        ]);
        $order = $this->order(['customer_id' => $customer->id, 'order_status' => 'packed']);
        $assignment = app(OrderStaffTaskService::class)->assign($order, OrderStaffAssignment::TASK_DELIVERY, $agent, $manager);

        return app(DeliveryWorkflowService::class)->startDelivery($assignment, $agent);
    }
}
