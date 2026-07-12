<?php

namespace App\Http\Controllers\Admin;

use App\Domains\ReturnRequest\Services\ReturnRequestService;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveReturnRequestRequest;
use App\Http\Requests\RejectReturnRequestRequest;
use App\Http\Requests\UpdateReturnStatusRequest;
use App\Models\ReturnRequest;
use Illuminate\Http\Request;
use InvalidArgumentException;

class AdminReturnController extends Controller
{
    public function __construct(
        private readonly ReturnRequestService $returnService
    ) {}

    public function index(Request $request)
    {
        $returns = $this->returnService->adminPaginate((int) $request->input('per_page', 20));

        return view('admin.returns.index', compact('returns'));
    }

    public function show(ReturnRequest $returnRequest)
    {
        $returnRequest->load(['order', 'customer', 'items.orderItem.productVariant']);

        return view('admin.returns.show', compact('returnRequest'));
    }

    public function approve(ReturnRequest $returnRequest, ApproveReturnRequestRequest $request)
    {
        $data = $request->validated();

        try {
            $this->returnService->approve($returnRequest, $data['admin_notes'] ?? null, (bool) ($data['restock_items'] ?? false));
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['return' => $exception->getMessage()]);
        }

        return back()->with('success', 'Return approved successfully.');
    }

    public function reject(ReturnRequest $returnRequest, RejectReturnRequestRequest $request)
    {
        try {
            $this->returnService->reject($returnRequest, $request->validated('admin_notes'));
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['return' => $exception->getMessage()]);
        }

        return back()->with('success', 'Return rejected successfully.');
    }

    public function markRefunded(ReturnRequest $returnRequest, UpdateReturnStatusRequest $request)
    {
        try {
            $this->returnService->markRefunded($returnRequest, $request->validated('admin_notes'));
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['return' => $exception->getMessage()]);
        }

        return back()->with('success', 'Return marked refunded.');
    }

    public function close(ReturnRequest $returnRequest, UpdateReturnStatusRequest $request)
    {
        try {
            $this->returnService->close($returnRequest, $request->validated('admin_notes'));
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['return' => $exception->getMessage()]);
        }

        return back()->with('success', 'Return closed successfully.');
    }
}
