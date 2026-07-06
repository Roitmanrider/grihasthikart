<?php

namespace App\Http\Controllers\Frontend;

use App\Domains\Customer\Services\CustomerAuthService;
use App\Domains\Wishlist\Services\WishlistService;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddToWishlistRequest;
use App\Models\WishlistItem;
use Illuminate\Http\Request;
use InvalidArgumentException;

class WishlistController extends Controller
{
    public function __construct(
        private readonly CustomerAuthService $authService,
        private readonly WishlistService $wishlistService
    ) {}

    public function index(Request $request)
    {
        $customer = $this->authService->currentCustomer($request->session());

        if (! $customer) {
            return redirect()->route('customer.login');
        }

        return view('frontend.wishlist.index', [
            'wishlistItems' => $this->wishlistService->itemsForCustomer($customer),
        ]);
    }

    public function store(AddToWishlistRequest $request)
    {
        $customer = $this->authService->currentCustomer($request->session());

        if (! $customer) {
            return redirect()->route('customer.login');
        }

        try {
            $this->wishlistService->add($customer, (int) $request->validated('product_variant_id'));
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['wishlist' => $exception->getMessage()]);
        }

        return back()->with('success', 'Item saved to wishlist.');
    }

    public function destroy(Request $request, WishlistItem $wishlistItem)
    {
        $customer = $this->authService->currentCustomer($request->session());

        if (! $customer) {
            return redirect()->route('customer.login');
        }

        try {
            $this->wishlistService->remove($customer, $wishlistItem);
        } catch (InvalidArgumentException) {
            abort(404);
        }

        return back()->with('success', 'Item removed from wishlist.');
    }
}
