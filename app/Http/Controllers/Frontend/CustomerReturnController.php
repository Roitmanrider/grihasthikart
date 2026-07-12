<?php

namespace App\Http\Controllers\Frontend;

use App\Domains\Customer\Services\CustomerAuthService;
use App\Domains\ReturnRequest\Services\ReturnRequestService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReturnRequestRequest;
use App\Models\Order;
use App\Models\ReturnRequest;
use Illuminate\Http\Request;
use InvalidArgumentException;

class CustomerReturnController extends Controller
{
    public function __construct(
        private readonly CustomerAuthService $authService,
        private readonly ReturnRequestService $returnService
    ) {}

    public function index(Request $request)
    {
        $customer = $this->requireCustomer();
        $returns = $this->returnService->customerPaginate($customer);

        return view('frontend.customer.returns.index', compact('customer', 'returns'));
    }

    public function create(Order $order)
    {
        $customer = $this->requireCustomer();
        abort_unless((int) $order->customer_id === (int) $customer->id, 404);

        $order->load('items');
        abort_unless($this->returnService->isEligible($order), 403);

        $remaining = $order->items->mapWithKeys(fn ($item) => [$item->id => $this->returnService->remainingReturnableQuantity($item)]);

        return view('frontend.customer.returns.create', compact('customer', 'order', 'remaining'));
    }

    public function store(StoreReturnRequestRequest $request)
    {
        $customer = $this->requireCustomer();

        try {
            $return = $this->returnService->create($customer, $request->validated());
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['return' => $exception->getMessage()]);
        }

        return redirect()
            ->route('customer.returns.show', $return)
            ->with('success', 'Return request submitted successfully.');
    }

    public function show(ReturnRequest $returnRequest)
    {
        $customer = $this->requireCustomer();
        abort_unless((int) $returnRequest->customer_id === (int) $customer->id, 404);
        $returnRequest->load(['order', 'items.orderItem']);

        return view('frontend.customer.returns.show', compact('customer', 'returnRequest'));
    }

    private function requireCustomer()
    {
        try {
            return $this->authService->requireCustomer(request()->session());
        } catch (InvalidArgumentException) {
            abort(redirect()->route('customer.login'));
        }
    }
}
