<?php

namespace App\Domains\Payment\Services;

use App\Domains\Payment\Contracts\PaymentRepositoryInterface;
use App\Domains\Payment\Gateways\CodPaymentGateway;
use App\Domains\Payment\Gateways\QrPaymentGateway;
use App\Domains\Payment\Gateways\RazorpayPaymentGateway;
use App\Domains\Setting\Services\BusinessSettingService;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Services\MediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PaymentService
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly BusinessSettingService $settings,
        private readonly MediaService $mediaService,
        private readonly CodPaymentGateway $codGateway,
        private readonly QrPaymentGateway $qrGateway,
        private readonly RazorpayPaymentGateway $razorpayGateway
    ) {}

    public function paginate(array $filters = [], int $perPage = 20)
    {
        return $this->paymentRepository->paginatedList($filters, $perPage);
    }

    public function enabledMethods(): array
    {
        $settings = $this->settings->paymentSettings();
        $methods = [];

        foreach (Payment::METHODS as $method) {
            if ((bool) ($settings[$method.'_enabled'] ?? false)) {
                $methods[] = $method;
            }
        }

        return $methods;
    }

    public function createForOrder(Order $order, string $method): Payment
    {
        $this->ensureMethodEnabled($method);

        if ($this->paymentRepository->activeForOrder($order)) {
            throw new InvalidArgumentException('This order already has an active payment.');
        }

        if ($method === 'razorpay' && ! $this->settings->razorpayConfigured()) {
            throw new InvalidArgumentException('Online payment is not configured yet.');
        }

        return DB::transaction(function () use ($order, $method) {
            $status = $method === 'qr' ? 'pending' : 'pending';
            $payment = $this->paymentRepository->create([
                'order_id' => $order->id,
                'payment_number' => $this->generatePaymentNumber(),
                'payment_method' => $method,
                'payment_status' => $status,
                'amount' => $order->grand_total,
                'currency' => $this->settings->get('payment.currency', 'INR'),
                'gateway' => $this->gatewayName($method),
            ]);

            $gatewayData = $this->gateway($method)->createPayment($order, $payment);

            $payment->update(collect($gatewayData)->only([
                'gateway',
                'gateway_order_id',
                'gateway_payment_id',
                'gateway_signature',
            ])->merge([
                'metadata' => collect($gatewayData)->only(['key_id', 'amount', 'currency', 'raw_response'])->all() ?: null,
            ])->all());

            $this->syncOrderPaymentStatus($order, $payment->payment_status, $method);
            $this->log($payment, 'initiated', $payment->payment_status, (float) $payment->amount, $gatewayData);

            return $payment->fresh(['order', 'transactions']);
        });
    }

    public function uploadProof(Order $order, UploadedFile $proof, ?string $reference = null): Payment
    {
        $payment = $this->paymentRepository->activeForOrder($order);

        if (! $payment || $payment->payment_method !== 'qr') {
            throw new InvalidArgumentException('Payment proof can only be uploaded for QR payments.');
        }

        if (in_array($payment->payment_status, ['paid', 'refunded', 'cancelled'], true)) {
            throw new InvalidArgumentException('This payment can no longer accept proof.');
        }

        $newPath = $this->mediaService->store($proof, 'payments/proofs');

        try {
            DB::transaction(function () use ($order, $payment, $reference, $newPath) {
                /** @var Payment $lockedPayment */
                $lockedPayment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
                $oldPath = $lockedPayment->proof_path;

                $lockedPayment->update([
                    'proof_path' => $newPath,
                    'qr_reference' => $reference,
                    'payment_status' => 'awaiting_verification',
                ]);

                $this->syncOrderPaymentStatus($order, 'awaiting_verification', 'qr');
                $this->log($lockedPayment, 'proof_submitted', 'awaiting_verification', (float) $lockedPayment->amount);
                DB::afterCommit(fn () => $this->mediaService->delete($oldPath));
            });
        } catch (\Throwable $exception) {
            $this->mediaService->delete($newPath);
            throw $exception;
        }

        return $payment->fresh(['order', 'transactions']);
    }

    public function verify(Payment $payment, ?string $note = null): Payment
    {
        return DB::transaction(function () use ($payment, $note) {
            /** @var Payment $lockedPayment */
            $lockedPayment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($lockedPayment->payment_status === 'paid') {
                throw new InvalidArgumentException('Payment has already been verified.');
            }

            if (in_array($lockedPayment->payment_status, ['refunded', 'cancelled'], true)) {
                throw new InvalidArgumentException('This payment can no longer be verified.');
            }

            $lockedPayment->update([
                'payment_status' => 'paid',
                'verified_at' => now(),
                'verified_by' => Auth::id(),
                'failure_reason' => null,
            ]);

            $this->syncOrderPaymentStatus($lockedPayment->order, 'paid', $lockedPayment->payment_method);
            $this->log($lockedPayment, 'verified', 'paid', (float) $lockedPayment->amount, note: $note);

            return $lockedPayment->fresh(['order', 'transactions']);
        });
    }

    public function fail(Payment $payment, string $reason): Payment
    {
        return DB::transaction(function () use ($payment, $reason) {
            /** @var Payment $lockedPayment */
            $lockedPayment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if (in_array($lockedPayment->payment_status, ['paid', 'refunded', 'cancelled'], true)) {
                throw new InvalidArgumentException('This payment can no longer be failed.');
            }

            $lockedPayment->update([
                'payment_status' => 'failed',
                'failure_reason' => $reason,
            ]);

            $this->syncOrderPaymentStatus($lockedPayment->order, 'failed', $lockedPayment->payment_method);
            $this->log($lockedPayment, 'failed', 'failed', (float) $lockedPayment->amount, note: $reason);

            return $lockedPayment->fresh(['order', 'transactions']);
        });
    }

    public function verifyRazorpayPayment(Payment $payment, array $payload): Payment
    {
        if ($payment->payment_method !== 'razorpay') {
            throw new InvalidArgumentException('This payment is not a Razorpay payment.');
        }

        if ($payment->gateway_order_id !== ($payload['razorpay_order_id'] ?? null)) {
            throw new InvalidArgumentException('Payment order reference does not match.');
        }

        if (! $this->razorpayGateway->verifyPayment($payload)) {
            $this->fail($payment, 'Razorpay signature verification failed.');

            throw new InvalidArgumentException('Payment verification failed.');
        }

        return DB::transaction(function () use ($payment, $payload) {
            /** @var Payment $lockedPayment */
            $lockedPayment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($lockedPayment->payment_status === 'paid') {
                return $lockedPayment->fresh(['order', 'transactions']);
            }

            if (in_array($lockedPayment->payment_status, ['refunded', 'cancelled'], true)) {
                throw new InvalidArgumentException('This payment can no longer be verified.');
            }

            $lockedPayment->update([
                'payment_status' => 'paid',
                'gateway_payment_id' => $payload['razorpay_payment_id'],
                'gateway_signature' => $payload['razorpay_signature'],
                'verified_at' => now(),
                'failure_reason' => null,
                'metadata' => array_merge($lockedPayment->metadata ?? [], [
                    'razorpay_verify_response' => [
                        'razorpay_order_id' => $payload['razorpay_order_id'],
                        'razorpay_payment_id' => $payload['razorpay_payment_id'],
                    ],
                ]),
            ]);

            $this->syncOrderPaymentStatus($lockedPayment->order, 'paid', 'razorpay');
            $this->log($lockedPayment, 'verified', 'paid', (float) $lockedPayment->amount, [
                'razorpay_order_id' => $payload['razorpay_order_id'],
                'razorpay_payment_id' => $payload['razorpay_payment_id'],
            ]);

            return $lockedPayment->fresh(['order', 'transactions']);
        });
    }

    public function updateSettings(array $data): void
    {
        foreach (['cod_enabled', 'qr_enabled', 'razorpay_enabled'] as $key) {
            $data[$key] = (bool) ($data[$key] ?? false);
        }

        $upload = $data['qr_image'] ?? null;
        unset($data['qr_image']);

        if ($upload instanceof UploadedFile) {
            $currentPath = $this->settings->get('payment.qr_image_path');
            $newPath = $this->mediaService->store($upload, 'payments/qr');
            $data['qr_image_path'] = $newPath;

            try {
                DB::transaction(fn () => $this->settings->updatePaymentSettings($data));
                $this->mediaService->delete($currentPath);

                return;
            } catch (\Throwable $exception) {
                $this->mediaService->delete($newPath);
                throw $exception;
            }
        }

        $this->settings->updatePaymentSettings($data);
    }

    public function ensureMethodEnabled(string $method): void
    {
        if (! in_array($method, Payment::METHODS, true)) {
            throw new InvalidArgumentException('Invalid payment method.');
        }

        if (! in_array($method, $this->enabledMethods(), true)) {
            throw new InvalidArgumentException('Selected payment method is currently unavailable.');
        }
    }

    public function generatePaymentNumber(): string
    {
        do {
            $number = 'PAY'.now()->format('ymd').Str::upper(Str::random(6));
        } while (Payment::query()->where('payment_number', $number)->exists());

        return $number;
    }

    private function gateway(string $method)
    {
        return match ($method) {
            'cod' => $this->codGateway,
            'qr' => $this->qrGateway,
            'razorpay' => $this->razorpayGateway,
        };
    }

    private function gatewayName(string $method): string
    {
        return match ($method) {
            'cod' => 'cod',
            'qr' => 'manual_qr',
            'razorpay' => 'razorpay',
        };
    }

    private function syncOrderPaymentStatus(Order $order, string $status, string $method): void
    {
        $order->update([
            'payment_method' => $method,
            'payment_status' => $status,
        ]);
    }

    private function log(Payment $payment, string $type, ?string $status = null, ?float $amount = null, array $payload = [], ?string $note = null): void
    {
        PaymentTransaction::query()->create([
            'payment_id' => $payment->id,
            'transaction_type' => $type,
            'status' => $status,
            'amount' => $amount,
            'payload' => $payload ?: null,
            'note' => $note,
            'created_by' => Auth::id(),
        ]);
    }
}
