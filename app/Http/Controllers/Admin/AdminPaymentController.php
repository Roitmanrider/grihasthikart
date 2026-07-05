<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Payment\Contracts\PaymentRepositoryInterface;
use App\Domains\Payment\Services\PaymentService;
use App\Http\Controllers\Controller;
use App\Http\Requests\FailPaymentRequest;
use App\Http\Requests\VerifyPaymentRequest;
use App\Models\Payment;
use Illuminate\Http\Request;
use InvalidArgumentException;

class AdminPaymentController extends Controller
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly PaymentService $paymentService
    ) {}

    public function index(Request $request)
    {
        $payments = $this->paymentService->paginate(
            $request->only(['search', 'payment_method', 'payment_status', 'date_from', 'date_to']),
            (int) $request->input('per_page', 20)
        );

        return view('admin.payments.index', compact('payments'));
    }

    public function show(Payment $payment)
    {
        $payment = $this->paymentRepository->findWithDetails($payment->id);

        return view('admin.payments.show', compact('payment'));
    }

    public function verify(Payment $payment, VerifyPaymentRequest $request)
    {
        try {
            $this->paymentService->verify($payment, $request->validated('note'));
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()]);
        }

        return redirect()->route('admin.payments.show', $payment)->with('success', 'Payment verified successfully.');
    }

    public function fail(Payment $payment, FailPaymentRequest $request)
    {
        try {
            $this->paymentService->fail($payment, $request->validated('failure_reason'));
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()]);
        }

        return redirect()->route('admin.payments.show', $payment)->with('success', 'Payment marked as failed.');
    }
}
