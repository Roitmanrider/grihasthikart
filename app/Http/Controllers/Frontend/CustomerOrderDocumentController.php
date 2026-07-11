<?php

namespace App\Http\Controllers\Frontend;

use App\Domains\Customer\Services\CustomerAuthService;
use App\Domains\Order\Services\OrderDocumentService;
use App\Http\Controllers\Controller;
use App\Models\Order;
use InvalidArgumentException;

class CustomerOrderDocumentController extends Controller
{
    public function __construct(
        private readonly CustomerAuthService $authService,
        private readonly OrderDocumentService $documents
    ) {}

    public function invoice(string $order)
    {
        try {
            $customer = $this->authService->requireCustomer(request()->session());
        } catch (InvalidArgumentException) {
            abort(redirect()->route('customer.login'));
        }

        $order = Order::query()
            ->where('customer_id', $customer->id)
            ->where('order_number', $order)
            ->firstOrFail();

        return view('documents.orders.invoice', $this->documents->invoiceData($order));
    }
}
