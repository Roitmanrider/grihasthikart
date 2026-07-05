<?php

namespace App\Domains\Payment\Gateways;

use App\Domains\Setting\Services\BusinessSettingService;
use App\Models\Order;
use App\Models\Payment;
use InvalidArgumentException;

class RazorpayPaymentGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly BusinessSettingService $settings
    ) {}

    public function createPayment(Order $order, Payment $payment): array
    {
        $keyId = $this->settings->get('payment.razorpay_key_id');
        $keySecret = $this->settings->get('payment.razorpay_key_secret');

        if (! $keyId || ! $keySecret) {
            throw new InvalidArgumentException('Online payment is not configured yet.');
        }

        return [
            'status' => 'pending',
            'gateway' => 'razorpay',
            'gateway_order_id' => 'stub_'.$payment->payment_number,
            'key_id' => $keyId,
        ];
    }

    public function verifyPayment(array $payload): bool
    {
        return false;
    }

    public function refund(Payment $payment, ?float $amount = null): array
    {
        throw new InvalidArgumentException('Razorpay refunds are not enabled in this MVP.');
    }
}
