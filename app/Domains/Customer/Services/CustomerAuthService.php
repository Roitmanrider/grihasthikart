<?php

namespace App\Domains\Customer\Services;

use App\Domains\Cart\Services\CartService;
use App\Domains\Customer\Contracts\CustomerRepositoryInterface;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\CustomerLoginOtp;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use InvalidArgumentException;

class CustomerAuthService
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customers,
        private readonly CartService $cartService,
        private readonly CustomerSessionService $customerSessionService
    ) {}

    public function requestOtp(string $mobile): string
    {
        $customer = $this->customers->findByMobile($mobile);

        if (! $customer) {
            throw new InvalidArgumentException('Your mobile number is not registered with GrihasthiKart.');
        }

        if (! $customer->status || $customer->trashed()) {
            throw new InvalidArgumentException('Your account is currently inactive. Please contact GrihasthiKart support.');
        }

        $otp = app()->environment('production') ? (string) random_int(100000, 999999) : '123456';

        CustomerLoginOtp::query()->create([
            'customer_id' => $customer->id,
            'mobile' => $customer->mobile,
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(10),
        ]);

        return $otp;
    }

    public function verifyOtp(Store $session, string $mobile, string $otp, ?Request $request = null): Customer
    {
        return DB::transaction(function () use ($session, $mobile, $otp, $request) {
            $record = CustomerLoginOtp::query()
                ->where('mobile', $mobile)
                ->whereNull('verified_at')
                ->latest()
                ->lockForUpdate()
                ->first();

            if (! $record || $record->expires_at->isPast()) {
                throw new InvalidArgumentException('OTP is invalid or expired.');
            }

            if ($record->attempts >= 5) {
                throw new InvalidArgumentException('Too many OTP attempts.');
            }

            $record->increment('attempts');

            if (! Hash::check($otp, $record->otp_hash)) {
                throw new InvalidArgumentException('OTP is invalid or expired.');
            }

            $record->update(['verified_at' => now()]);
            $customer = $record->customer;

            if (! $customer || ! $customer->status || $customer->trashed()) {
                throw new InvalidArgumentException('Your account is currently inactive. Please contact GrihasthiKart support.');
            }

            $session->regenerate();
            $customer->update(['last_login_at' => now()]);
            $session->put('customer_id', $customer->id);
            $this->customerSessionService->start($customer, $session, $request);
            $this->attachSessionCartToCustomer($session, $customer);

            return $customer;
        });
    }

    public function currentCustomer(Store $session): ?Customer
    {
        $id = $session->get('customer_id');

        if (! $id) {
            return null;
        }

        $customer = Customer::query()->find($id);

        if (! $customer || ! $customer->status || $customer->trashed()) {
            $this->logout($session);

            return null;
        }

        if (! $session->get(CustomerSessionService::SESSION_TOKEN_KEY)) {
            $this->customerSessionService->start($customer, $session);
        }

        if (! $this->customerSessionService->validate($session)) {
            $this->logout($session);

            return null;
        }

        return $customer;
    }

    public function requireCustomer(Store $session): Customer
    {
        $id = $session->get('customer_id');

        if (! $id) {
            throw new InvalidArgumentException('Please login to continue.');
        }

        $customer = Customer::query()->find($id);

        if (! $customer) {
            throw new InvalidArgumentException('Please login to continue.');
        }

        if (! $customer->status || $customer->trashed()) {
            $this->logout($session);
            throw new InvalidArgumentException('Your account is currently inactive. Please contact GrihasthiKart support.');
        }

        if (! $session->get(CustomerSessionService::SESSION_TOKEN_KEY)) {
            $this->customerSessionService->start($customer, $session);
        }

        if (! $this->customerSessionService->validate($session)) {
            $this->logout($session);
            throw new InvalidArgumentException('For your security, please log in again.');
        }

        return $customer;
    }

    public function logout(Store $session): void
    {
        $this->customerSessionService->revokeCurrent($session);
        $session->forget('customer_id');
    }

    public function attachSessionCartToCustomer(Store $session, Customer $customer): void
    {
        $guestSessionId = (string) ($session->get('cart_session_id') ?: $session->getId());
        $customerSessionId = 'customer:'.$customer->id;
        $sessionCart = $guestSessionId !== $customerSessionId
            ? $this->cartService->getOrCreateCartForSession($guestSessionId)
            : null;
        $existingCart = $customer->carts()->active()->first()
            ?? $this->cartService->getOrCreateCartForSession($customerSessionId);

        if (! $sessionCart || $sessionCart->id === $existingCart->id) {
            $existingCart->update(['session_id' => $customerSessionId, 'customer_id' => $customer->id]);
            $this->cartService->syncPendingLifecycle($existingCart);
            $session->put('cart_session_id', $customerSessionId);

            return;
        }

        foreach ($sessionCart->items as $item) {
            $existingItem = CartItem::query()
                ->where('cart_id', $existingCart->id)
                ->where('product_variant_id', $item->product_variant_id)
                ->first();

            if ($existingItem) {
                $existingItem->increment('quantity', (float) $item->quantity);
                $item->delete();
            } else {
                $item->update(['cart_id' => $existingCart->id]);
            }
        }

        $sessionCart->update(['status' => 'merged']);
        $existingCart->update(['session_id' => $customerSessionId, 'customer_id' => $customer->id]);
        $this->cartService->recordCartMutation($existingCart);
        $this->cartService->syncPendingLifecycle($existingCart);
        $session->put('cart_session_id', $customerSessionId);
    }
}
