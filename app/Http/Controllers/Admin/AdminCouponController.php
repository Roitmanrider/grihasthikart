<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Coupon\Contracts\CouponRepositoryInterface;
use App\Domains\Coupon\Services\CouponService;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkCouponActionRequest;
use App\Http\Requests\StoreCouponRequest;
use App\Http\Requests\UpdateCouponRequest;
use App\Models\Coupon;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class AdminCouponController extends Controller
{
    public function __construct(
        private readonly CouponService $couponService,
        private readonly CouponRepositoryInterface $couponRepository
    ) {}

    public function index(Request $request)
    {
        $coupons = $this->couponService->paginate(
            $request->only(['search', 'status', 'discount_type', 'is_cashback_coupon', 'source', 'validity', 'customer_specific', 'trashed']),
            (int) $request->input('per_page', 20)
        );

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create()
    {
        $customers = Customer::query()->orderBy('name')->limit(100)->get();

        return view('admin.coupons.create', compact('customers'));
    }

    public function store(StoreCouponRequest $request)
    {
        try {
            $this->couponService->create($request->validated());
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['coupon' => $exception->getMessage()]);
        }

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon created successfully.');
    }

    public function show(Coupon $coupon)
    {
        $coupon = $this->couponRepository->findWithDetails($coupon->id);

        return view('admin.coupons.show', compact('coupon'));
    }

    public function edit(Coupon $coupon)
    {
        $customers = Customer::query()->orderBy('name')->limit(100)->get();

        return view('admin.coupons.edit', compact('coupon', 'customers'));
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon)
    {
        try {
            $this->couponService->update($coupon, $request->validated());
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['coupon' => $exception->getMessage()]);
        }

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon updated successfully.');
    }

    public function destroy(Coupon $coupon)
    {
        Gate::authorize('manage-coupons');
        $this->couponService->delete($coupon);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon deleted successfully.');
    }

    public function restore(int $coupon)
    {
        Gate::authorize('manage-coupons');
        $this->couponService->restore($coupon);

        return redirect()->route('admin.coupons.index', ['trashed' => 'with'])->with('success', 'Coupon restored successfully.');
    }

    public function bulkAction(BulkCouponActionRequest $request)
    {
        $data = $request->validated();
        $count = match ($data['action']) {
            'delete' => $this->couponService->bulkDelete($data['ids']),
            'activate' => $this->couponService->bulkUpdateStatus($data['ids'], true),
            'deactivate' => $this->couponService->bulkUpdateStatus($data['ids'], false),
            'restore' => $this->couponService->bulkRestore($data['ids']),
        };

        return redirect()->route('admin.coupons.index', $request->query())->with('success', $count.' coupons processed successfully.');
    }
}
