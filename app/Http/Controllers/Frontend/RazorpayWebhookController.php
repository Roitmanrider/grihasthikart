<?php

namespace App\Http\Controllers\Frontend;

use App\Domains\Order\Services\OrderService;
use App\Domains\Payment\Services\PaymentService;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\RazorpayWebhookEvent;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use InvalidArgumentException;

class RazorpayWebhookController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly OrderService $orderService
    ) {}

    public function __invoke(Request $request)
    {
        $rawBody = $request->getContent();

        try {
            if (! $this->paymentService->verifyRazorpayWebhookSignature($rawBody, $request->header('X-Razorpay-Signature'))) {
                return response()->json(['message' => 'Invalid webhook signature.'], 400);
            }
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $payload = json_decode($rawBody, true) ?: [];
        $providerPayment = data_get($payload, 'payload.payment.entity', []);
        $eventType = (string) ($payload['event'] ?? 'unknown');
        $payloadHash = hash('sha256', $rawBody);
        $eventId = (string) ($payload['id'] ?? 'payload-'.$payloadHash);

        try {
            $event = RazorpayWebhookEvent::query()->firstOrCreate(
                ['event_id' => $eventId],
                [
                    'event_type' => $eventType,
                    'gateway_order_id' => $providerPayment['order_id'] ?? null,
                    'gateway_payment_id' => $providerPayment['id'] ?? null,
                    'payload_hash' => $payloadHash,
                    'status' => RazorpayWebhookEvent::STATUS_RECEIVED,
                    'payload' => $payload,
                ]
            );
        } catch (QueryException $exception) {
            $event = RazorpayWebhookEvent::query()->where('event_id', $eventId)->firstOrFail();
        }

        if ($event->status === RazorpayWebhookEvent::STATUS_PROCESSED) {
            return response()->json(['status' => 'processed']);
        }

        if (! in_array($eventType, ['payment.captured', 'payment.authorized', 'order.paid'], true)) {
            $event->update([
                'status' => RazorpayWebhookEvent::STATUS_PROCESSED,
                'processed_at' => now(),
            ]);

            return response()->json(['status' => 'ignored']);
        }

        $payment = Payment::query()
            ->where('payment_method', 'razorpay')
            ->where('gateway_order_id', $providerPayment['order_id'] ?? null)
            ->latest()
            ->first();

        if (! $payment) {
            $event->update([
                'status' => RazorpayWebhookEvent::STATUS_FAILED,
                'failure_reason' => 'Matching Razorpay payment was not found.',
            ]);

            return response()->json(['status' => 'pending_match']);
        }

        try {
            $order = $this->orderService->completeRazorpayPaymentFromWebhook($payment, $providerPayment, $payload);
            $event->update([
                'order_id' => $order->id,
                'payment_id' => $order->payment?->id,
                'status' => RazorpayWebhookEvent::STATUS_PROCESSED,
                'processed_at' => now(),
                'failure_reason' => null,
            ]);
        } catch (InvalidArgumentException $exception) {
            $event->update([
                'payment_id' => $payment->id,
                'status' => RazorpayWebhookEvent::STATUS_FAILED,
                'failure_reason' => $exception->getMessage(),
            ]);

            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['status' => 'processed']);
    }
}
