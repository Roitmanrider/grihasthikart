<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PendingOrder;
use Illuminate\Http\Request;

class AdminPendingOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = PendingOrder::query()
            ->with(['customer', 'convertedOrder', 'activeItems'])
            ->withCount('activeItems')
            ->latest('started_at');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }

        if ($request->filled('from')) {
            $query->whereDate('started_at', '>=', (string) $request->date('from')?->toDateString());
        }

        if ($request->filled('to')) {
            $query->whereDate('started_at', '<=', (string) $request->date('to')?->toDateString());
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->string('search'));
            $query->where(function ($query) use ($search) {
                $query->where('reference', 'like', '%'.$search.'%')
                    ->orWhereHas('customer', function ($query) use ($search) {
                        $query->where('name', 'like', '%'.$search.'%')
                            ->orWhere('mobile', 'like', '%'.$search.'%');
                    });
            });
        }

        $pendingOrders = $query->paginate(20)->withQueryString();

        return view('admin.pending-orders.index', compact('pendingOrders'));
    }

    public function show(PendingOrder $pendingOrder)
    {
        $pendingOrder->load(['customer', 'convertedOrder', 'items.productVariant']);

        return view('admin.pending-orders.show', compact('pendingOrder'));
    }
}
