<?php

namespace App\Domains\Payment\Gateways;

use App\Models\Order;
use App\Models\Payment;

interface PaymentGatewayInterface
{
    public function createPayment(Order $order, Payment $payment): array;

    public function verifyPayment(array $payload): bool;

    public function refund(Payment $payment, ?float $amount = null): array;
}
