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
use InvalidArgumentException;

class CustomerAuthService
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customers,
        private readonly CartService $cartService
    ) {}

    public function requestOtp(string $mobile): string
    {
        $customer = $this->customers->findByMobile($mobile);

        if (! $customer || ! $customer->status || $customer->trashed()) {
            throw new InvalidArgumentException('No active customer account found for this mobile number.');
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

    public function verifyOtp(Store $session, string $mobile, string $otp): Customer
    {
        return DB::transaction(function () use ($session, $mobile, $otp) {
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
            $customer->update(['last_login_at' => now()]);
            $session->put('customer_id', $customer->id);
            $this->attachSessionCartToCustomer($session, $customer);

            return $customer;
        });
    }

    public function currentCustomer(Store $session): ?Customer
    {
        $id = $session->get('customer_id');

        return $id ? Customer::query()->find($id) : null;
    }

    public function requireCustomer(Store $session): Customer
    {
        $customer = $this->currentCustomer($session);

        if (! $customer) {
            throw new InvalidArgumentException('Please login to continue.');
        }

        return $customer;
    }

    public function logout(Store $session): void
    {
        $session->forget('customer_id');
    }

    public function attachSessionCartToCustomer(Store $session, Customer $customer): void
    {
        $sessionId = $this->cartService->sessionIdentifier($session);
        $sessionCart = $this->cartService->getOrCreateCartForSession($sessionId);
        $existingCart = $customer->carts()->active()->whereKeyNot($sessionCart->id)->first();

        if (! $existingCart) {
            $sessionCart->update(['customer_id' => $customer->id]);

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
        $existingCart->update(['session_id' => $sessionId, 'customer_id' => $customer->id]);
    }
}
