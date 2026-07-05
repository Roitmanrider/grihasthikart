<?php

namespace App\Domains\Payment\Gateways;

use App\Models\Order;
use App\Models\Payment;

class CodPaymentGateway implements PaymentGatewayInterface
{
    public function createPayment(Order $order, Payment $payment): array
    {
        return ['status' => 'pending', 'gateway' => 'cod'];
    }

    public function verifyPayment(array $payload): bool
    {
        return true;
    }

    public function refund(Payment $payment, ?float $amount = null): array
    {
        return ['status' => 'not_applicable'];
    }
}
