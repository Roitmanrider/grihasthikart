<?php

namespace App\Domains\Payment\Gateways;

use App\Domains\Setting\Services\BusinessSettingService;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
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

        $amount = (int) round((float) $payment->amount * 100);
        $currency = (string) ($payment->currency ?: $this->settings->get('payment.currency', 'INR'));
        $response = Http::withBasicAuth($keyId, $keySecret)
            ->acceptJson()
            ->asJson()
            ->post('https://api.razorpay.com/v1/orders', [
                'amount' => $amount,
                'currency' => $currency,
                'receipt' => $payment->payment_number,
                'notes' => [
                    'order_number' => $order->order_number,
                    'payment_number' => $payment->payment_number,
                ],
            ]);

        if (! $response->successful()) {
            throw new InvalidArgumentException('Unable to initiate online payment. Please try again.');
        }

        $payload = $response->json();

        if (empty($payload['id'])) {
            throw new InvalidArgumentException('Unable to initiate online payment. Please try again.');
        }

        return [
            'status' => 'pending',
            'gateway' => 'razorpay',
            'gateway_order_id' => $payload['id'] ?? null,
            'key_id' => $keyId,
            'amount' => $amount,
            'currency' => $currency,
            'raw_response' => $payload,
        ];
    }

    public function verifyPayment(array $payload): bool
    {
        $keySecret = $this->settings->get('payment.razorpay_key_secret');

        if (! $keySecret) {
            throw new InvalidArgumentException('Online payment is not configured yet.');
        }

        $expected = hash_hmac(
            'sha256',
            ($payload['razorpay_order_id'] ?? '').'|'.($payload['razorpay_payment_id'] ?? ''),
            $keySecret
        );

        return hash_equals($expected, (string) ($payload['razorpay_signature'] ?? ''));
    }

    public function refund(Payment $payment, ?float $amount = null): array
    {
        throw new InvalidArgumentException('Razorpay refunds are not enabled in this MVP.');
    }
}
