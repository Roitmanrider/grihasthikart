<?php

namespace App\Domains\Coupon\Services;

use App\Domains\Coupon\Contracts\CouponRepositoryInterface;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class CouponService
{
    public function __construct(
        private readonly CouponRepositoryInterface $couponRepository
    ) {}

    public function paginate(array $filters = [], int $perPage = 20)
    {
        return $this->couponRepository->paginatedList($filters, $perPage);
    }

    public function create(array $data): Coupon
    {
        $data = $this->prepareData($data);
        $data['created_by'] = Auth::id();
        $this->validateDiscountShape($data);

        /** @var Coupon $coupon */
        $coupon = $this->couponRepository->create($data);

        return $coupon;
    }

    public function update(Coupon $coupon, array $data): Coupon
    {
        $data = $this->prepareData($data);
        $data['updated_by'] = Auth::id();
        $this->validateDiscountShape($data);

        /** @var Coupon $coupon */
        $coupon = $this->couponRepository->update($coupon, $data);

        return $coupon;
    }

    public function delete(Coupon $coupon): bool
    {
        return (bool) $this->couponRepository->delete($coupon);
    }

    public function restore(int $couponId): Coupon
    {
        /** @var Coupon $coupon */
        $coupon = Coupon::withTrashed()->findOrFail($couponId);
        $coupon->restore();

        return $coupon;
    }

    public function bulkUpdateStatus(array $ids, bool $status): int
    {
        return Coupon::query()->whereIn('id', $ids)->update(['status' => $status]);
    }

    public function bulkDelete(array $ids): int
    {
        $count = 0;

        Coupon::query()->whereIn('id', $ids)->get()->each(function (Coupon $coupon) use (&$count) {
            $coupon->delete();
            $count++;
        });

        return $count;
    }

    public function bulkRestore(array $ids): int
    {
        return Coupon::onlyTrashed()->whereIn('id', $ids)->restore();
    }

    public function normalizeCode(string $code): string
    {
        return str($code)->upper()->replaceMatches('/\s+/', '')->toString();
    }

    public function validateCouponForCart(Cart $cart, string $code, ?Customer $customer = null): Coupon
    {
        $coupon = $this->couponRepository->findByCode($this->normalizeCode($code));

        if (! $coupon) {
            throw new InvalidArgumentException('Coupon code is invalid.');
        }

        $this->ensureCouponIsActive($coupon);
        $this->ensureCouponWithinDateWindow($coupon);
        $this->ensureMinimumOrderAmount($coupon, $cart);
        $this->ensureCustomerEligibility($coupon, $customer);
        $this->ensureUsageLimits($coupon, $cart, $customer);

        return $coupon;
    }

    public function applyCouponToCart(Cart $cart, string $code, ?Customer $customer = null): Cart
    {
        $coupon = $this->validateCouponForCart($cart, $code, $customer);
        $discount = $this->calculateDiscount($coupon, $cart);

        $cart->update([
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'coupon_discount_amount' => $discount,
        ]);

        return $cart->fresh(['items', 'coupon']);
    }

    public function removeCouponFromCart(Cart $cart): Cart
    {
        $cart->update([
            'coupon_id' => null,
            'coupon_code' => null,
            'coupon_discount_amount' => 0,
        ]);

        return $cart->fresh(['items']);
    }

    public function calculateDiscount(Coupon $coupon, Cart $cart): float
    {
        $subtotal = $this->cartSubtotal($cart);

        if ($coupon->discount_type === 'fixed') {
            return round(min((float) $coupon->discount_value, $subtotal), 2);
        }

        $discount = $subtotal * (float) $coupon->discount_value / 100;

        if ($coupon->max_discount_amount !== null) {
            $discount = min($discount, (float) $coupon->max_discount_amount);
        }

        return round(min($discount, $subtotal), 2);
    }

    public function getAppliedCouponForCart(Cart $cart): ?Coupon
    {
        return $cart->coupon_id ? Coupon::query()->find($cart->coupon_id) : null;
    }

    public function createUsageForOrder(Order $order, Coupon $coupon, float $discountAmount): CouponUsage
    {
        return CouponUsage::query()->create([
            'coupon_id' => $coupon->id,
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'session_id' => $order->session_id,
            'code_snapshot' => $coupon->code,
            'discount_type_snapshot' => $coupon->discount_type,
            'discount_value_snapshot' => $coupon->discount_value,
            'discount_amount' => $discountAmount,
            'cart_subtotal_snapshot' => $order->subtotal,
            'used_at' => now(),
        ]);
    }

    public function clearCouponAfterOrder(Cart $cart): void
    {
        $this->removeCouponFromCart($cart);
    }

    public function ensureCouponIsActive(Coupon $coupon): void
    {
        if (! $coupon->status || $coupon->trashed()) {
            throw new InvalidArgumentException('Coupon is inactive.');
        }
    }

    public function ensureCouponWithinDateWindow(Coupon $coupon): void
    {
        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            throw new InvalidArgumentException('Coupon is not active yet.');
        }

        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            throw new InvalidArgumentException('Coupon has expired.');
        }
    }

    public function ensureMinimumOrderAmount(Coupon $coupon, Cart $cart): void
    {
        if ($this->cartSubtotal($cart) < (float) $coupon->minimum_order_amount) {
            throw new InvalidArgumentException('Coupon requires a minimum cart subtotal of Rs. '.number_format((float) $coupon->minimum_order_amount, 2).'.');
        }
    }

    public function ensureCustomerEligibility(Coupon $coupon, ?Customer $customer = null): void
    {
        if ($coupon->customer_id && $coupon->customer_id !== $customer?->id) {
            throw new InvalidArgumentException('Coupon is not available for this customer.');
        }
    }

    public function ensureUsageLimits(Coupon $coupon, Cart $cart, ?Customer $customer = null): void
    {
        if ($coupon->usage_limit_total !== null && $coupon->usages()->count() >= $coupon->usage_limit_total) {
            throw new InvalidArgumentException('Coupon usage limit has been reached.');
        }

        if ($customer && $coupon->usage_limit_per_customer !== null) {
            $count = $coupon->usages()->where('customer_id', $customer->id)->count();

            if ($count >= $coupon->usage_limit_per_customer) {
                throw new InvalidArgumentException('Customer coupon usage limit has been reached.');
            }

            return;
        }

        if ($coupon->usage_limit_per_session !== null) {
            $count = $coupon->usages()->where('session_id', $cart->session_id)->count();

            if ($count >= $coupon->usage_limit_per_session) {
                throw new InvalidArgumentException('Session coupon usage limit has been reached.');
            }
        }
    }

    public function revalidateAppliedCoupon(Cart $cart, ?Customer $customer = null): array
    {
        if (! $cart->coupon_code) {
            return ['coupon' => null, 'discount' => 0.0];
        }

        $coupon = $this->validateCouponForCart($cart, $cart->coupon_code, $customer);
        $discount = $this->calculateDiscount($coupon, $cart);

        $cart->update([
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'coupon_discount_amount' => $discount,
        ]);

        return ['coupon' => $coupon, 'discount' => $discount];
    }

    private function prepareData(array $data): array
    {
        $data['code'] = $this->normalizeCode($data['code']);
        $data['status'] = (bool) ($data['status'] ?? false);
        $data['is_cashback_coupon'] = (bool) ($data['is_cashback_coupon'] ?? false);
        $data['source'] = ($data['source'] ?? null) ?: 'admin';
        $data['minimum_order_amount'] = $data['minimum_order_amount'] ?? 0;

        foreach (['customer_id', 'usage_limit_total', 'usage_limit_per_customer', 'usage_limit_per_session', 'max_discount_amount'] as $field) {
            $data[$field] = ($data[$field] ?? null) === '' ? null : ($data[$field] ?? null);
        }

        return $data;
    }

    private function validateDiscountShape(array $data): void
    {
        if ($data['discount_type'] === 'percentage' && ((float) $data['discount_value'] <= 0 || (float) $data['discount_value'] > 100)) {
            throw new InvalidArgumentException('Percentage discount must be between 0 and 100.');
        }

        if ($data['discount_type'] === 'fixed' && (float) $data['discount_value'] <= 0) {
            throw new InvalidArgumentException('Fixed discount must be greater than zero.');
        }
    }

    private function cartSubtotal(Cart $cart): float
    {
        $cart->loadMissing('items');

        return (float) $cart->items->sum(fn ($item) => $item->line_total);
    }
}
