<?php

namespace App\Domains\Payment\Gateways;

use App\Models\Order;
use App\Models\Payment;

class QrPaymentGateway implements PaymentGatewayInterface
{
    public function createPayment(Order $order, Payment $payment): array
    {
        return ['status' => $payment->payment_status, 'gateway' => 'manual_qr'];
    }

    public function verifyPayment(array $payload): bool
    {
        return true;
    }

    public function refund(Payment $payment, ?float $amount = null): array
    {
        return ['status' => 'manual_review_required'];
    }
}
