<?php

namespace Tests\Feature;

use App\Domains\Setting\Services\BusinessSettingService;
use App\Models\CartItem;
use App\Models\DeliverySlot;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\BusinessSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);
        $this->seed(BusinessSettingSeeder::class);
        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        DeliverySlot::factory()->create([
            'name' => '9-11 AM',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'display_label' => '9-11 AM',
            'status' => true,
        ]);
    }

    public function test_cod_checkout_creates_pending_payment_record(): void
    {
        [, $variant] = $this->cartItem();
        $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->post(route('checkout.place'), $this->checkoutPayload())->assertRedirect();

        $order = Order::query()->firstOrFail();
        $payment = Payment::query()->firstOrFail();

        $this->assertSame('cod', $payment->payment_method);
        $this->assertSame('pending', $payment->payment_status);
        $this->assertSame($order->grand_total, $payment->amount);
        $this->assertDatabaseHas('payment_transactions', [
            'payment_id' => $payment->id,
            'transaction_type' => 'initiated',
        ]);
    }

    public function test_qr_enabled_appears_and_disabled_does_not_appear_on_checkout(): void
    {
        [, $variant] = $this->cartItem();
        $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->get(route('checkout.show'))->assertOk()->assertDontSee('Pay by QR');

        app(BusinessSettingService::class)->set('payment.qr_enabled', true);

        $this->get(route('checkout.show'))->assertOk()->assertSee('Pay by QR');
    }

    public function test_qr_proof_upload_is_session_protected_and_sets_awaiting_verification(): void
    {
        Storage::fake('public');
        app(BusinessSettingService::class)->set('payment.qr_enabled', true);
        [, $variant] = $this->cartItem();
        $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $this->post(route('checkout.place'), array_merge($this->checkoutPayload(), ['payment_method' => 'qr']));
        $order = Order::query()->firstOrFail();
        $orderSessionId = $order->session_id;

        $this->withSession(['cart_session_id' => 'other-session'])
            ->post(route('orders.payment-proof.store', $order->order_number), [
                'proof' => UploadedFile::fake()->image('proof.jpg'),
            ])->assertNotFound();

        $this->withSession(['cart_session_id' => $orderSessionId])
            ->post(route('orders.payment-proof.store', $order->order_number), [
                'proof' => UploadedFile::fake()->image('proof.jpg'),
                'qr_reference' => 'UTR123',
            ])->assertSessionHasNoErrors();

        $payment = Payment::query()->firstOrFail();
        $this->assertSame('awaiting_verification', $payment->fresh()->payment_status);
        $this->assertSame('awaiting_verification', $order->fresh()->payment_status);
        Storage::disk('public')->assertExists($payment->fresh()->proof_path);
    }

    public function test_admin_can_verify_and_fail_qr_payments(): void
    {
        $order = Order::factory()->create(['payment_method' => 'qr', 'payment_status' => 'awaiting_verification']);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'payment_method' => 'qr',
            'payment_status' => 'awaiting_verification',
            'amount' => $order->grand_total,
        ]);

        $this->actingAs($this->admin)->get(route('admin.payments.index'))->assertOk()->assertSee($payment->payment_number);
        $this->actingAs($this->admin)->get(route('admin.payments.show', $payment))->assertOk()->assertSee('Transaction Log');
        $this->actingAs($this->admin)->patch(route('admin.payments.verify', $payment), ['note' => 'Matched'])
            ->assertRedirect(route('admin.payments.show', $payment));

        $this->assertSame('paid', $payment->fresh()->payment_status);
        $this->assertSame('paid', $order->fresh()->payment_status);

        $failedOrder = Order::factory()->create(['payment_method' => 'qr', 'payment_status' => 'awaiting_verification']);
        $failedPayment = Payment::factory()->create([
            'order_id' => $failedOrder->id,
            'payment_method' => 'qr',
            'payment_status' => 'awaiting_verification',
            'amount' => $failedOrder->grand_total,
        ]);

        $this->actingAs($this->admin)->patch(route('admin.payments.fail', $failedPayment), [
            'failure_reason' => 'Amount not received',
        ])->assertRedirect(route('admin.payments.show', $failedPayment));

        $this->assertSame('failed', $failedPayment->fresh()->payment_status);
        $this->assertSame('failed', $failedOrder->fresh()->payment_status);
    }

    public function test_payment_settings_update_methods_and_do_not_expose_secret_on_checkout(): void
    {
        $this->actingAs($this->admin)->patch(route('admin.settings.payments.update'), [
            'cod_enabled' => 1,
            'qr_enabled' => 1,
            'razorpay_enabled' => 1,
            'qr_label' => 'Scan and Pay',
            'qr_upi_id' => 'grihasthikart@upi',
            'qr_display_name' => 'GrihasthiKart',
            'razorpay_key_id' => 'rzp_test_123',
            'razorpay_key_secret' => 'secret-value',
            'currency' => 'INR',
        ])->assertRedirect(route('admin.settings.payments.edit'));

        $service = app(BusinessSettingService::class);
        $this->assertTrue($service->get('payment.qr_enabled'));
        $this->assertSame('secret-value', $service->get('payment.razorpay_key_secret'));

        [, $variant] = $this->cartItem();
        $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $this->get(route('checkout.show'))->assertOk()->assertSee('Scan and Pay')->assertDontSee('secret-value');
    }

    public function test_razorpay_enabled_without_credentials_fails_gracefully(): void
    {
        app(BusinessSettingService::class)->set('payment.razorpay_enabled', true);
        [, $variant] = $this->cartItem();
        $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->post(route('checkout.place'), array_merge($this->checkoutPayload(), [
            'payment_method' => 'razorpay',
        ]))->assertSessionHasErrors('checkout');

        $this->assertSame(0, Order::query()->count());
        $this->assertSame(1, CartItem::query()->count());
    }

    public function test_payment_admin_routes_require_authorization(): void
    {
        $user = User::factory()->create(['email' => 'customer@example.com']);
        $payment = Payment::factory()->create();

        $this->actingAs($user)->get(route('admin.payments.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.settings.payments.edit'))->assertForbidden();
        $this->actingAs($user)->patch(route('admin.payments.verify', $payment))->assertForbidden();
    }

    private function cartItem(): array
    {
        $product = Product::factory()->create([
            'name' => 'Wheat Atta',
            'status' => true,
            'hsn_code' => '1101',
            'gst_rate' => 5,
        ]);
        $variant = ProductVariant::factory()->default()->create([
            'product_id' => $product->id,
            'variant_name' => '1kg',
            'sku' => fake()->unique()->bothify('GK-ATTA-####'),
            'mrp' => 75,
            'selling_price' => 68,
            'status' => true,
        ]);
        $product->update(['default_variant_id' => $variant->id]);
        $inventory = Inventory::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 10,
            'reserved_quantity' => 0,
            'damaged_quantity' => 0,
            'status' => true,
        ]);

        return [$product, $variant, $inventory];
    }

    private function checkoutPayload(): array
    {
        return [
            'customer_name' => 'Rohit Kumar',
            'customer_mobile' => '9876543210',
            'customer_email' => 'rohit@example.com',
            'delivery_address_line1' => 'House 12, Main Road',
            'delivery_address_line2' => 'Near Market',
            'delivery_city' => 'Patna',
            'delivery_state' => 'Bihar',
            'delivery_pincode' => '800001',
            'delivery_landmark' => 'Clock Tower',
            'delivery_date' => now()->addDay()->toDateString(),
            'delivery_slot' => '9-11 AM',
            'payment_method' => 'cod',
        ];
    }
}
