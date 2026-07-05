<?php

namespace App\Http\Controllers\Frontend;

use App\Domains\Cart\Services\CartService;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddToCartRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Models\CartItem;
use InvalidArgumentException;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService
    ) {}

    public function show()
    {
        $summary = $this->cartService->getCartSummary($this->cartService->sessionIdentifier(request()->session()));

        return view('frontend.cart.show', $summary);
    }

    public function store(AddToCartRequest $request)
    {
        $data = $request->validated();

        try {
            $this->cartService->addItem(
                $this->cartService->sessionIdentifier($request->session()),
                (int) $data['product_variant_id'],
                (float) $data['quantity']
            );
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['cart' => $exception->getMessage()]);
        }

        return redirect()
            ->route('cart.show')
            ->with('success', 'Item added to cart.');
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem)
    {
        try {
            $data = $request->validated();

            $this->cartService->updateItemQuantity(
                $this->cartService->sessionIdentifier($request->session()),
                $cartItem,
                (float) $data['quantity']
            );
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withErrors(['cart' => $exception->getMessage()]);
        }

        return redirect()
            ->route('cart.show')
            ->with('success', 'Cart item updated.');
    }

    public function destroy(CartItem $cartItem)
    {
        try {
            $this->cartService->removeItem($this->cartService->sessionIdentifier(request()->session()), $cartItem);
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withErrors(['cart' => $exception->getMessage()]);
        }

        return redirect()
            ->route('cart.show')
            ->with('success', 'Cart item removed.');
    }

    public function clear()
    {
        $this->cartService->clearCart($this->cartService->sessionIdentifier(request()->session()));

        return redirect()
            ->route('cart.show')
            ->with('success', 'Cart cleared.');
    }
}
