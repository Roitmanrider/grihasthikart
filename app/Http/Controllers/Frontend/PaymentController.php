<?php

namespace App\Http\Controllers\Frontend;

use App\Domains\Cart\Services\CartService;
use App\Domains\Order\Contracts\OrderRepositoryInterface;
use App\Domains\Payment\Services\PaymentService;
use App\Http\Controllers\Controller;
use App\Http\Requests\UploadPaymentProofRequest;
use InvalidArgumentException;

class PaymentController extends Controller
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CartService $cartService,
        private readonly PaymentService $paymentService
    ) {}

    public function uploadProof(string $orderNumber, UploadPaymentProofRequest $request)
    {
        $order = $this->orderRepository->findByOrderNumberForSession(
            $orderNumber,
            $this->cartService->sessionIdentifier($request->session())
        );

        try {
            $this->paymentService->uploadProof(
                $order,
                $request->file('proof'),
                $request->validated('qr_reference')
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()]);
        }

        return back()->with('success', 'Payment proof uploaded successfully.');
    }
}
