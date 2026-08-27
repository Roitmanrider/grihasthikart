<?php

namespace App\Domains\Staff\Services;

use App\Models\Customer;
use App\Models\DeliveryOtp;
use App\Models\Notification;
use App\Models\Order;
use Illuminate\Support\Facades\Crypt;

class DeliveryOtpAccessService
{
    public function activeCodeForCustomerOrder(Customer $customer, Order $order): ?string
    {
        if ((int) $order->customer_id !== (int) $customer->id) {
            return null;
        }

        $credential = DeliveryOtp::query()
            ->where('order_id', $order->id)
            ->whereNull('used_at')
            ->whereNull('invalidated_at')
            ->where('expires_at', '>', now())
            ->whereHas('attempt', fn ($query) => $query->where('status', 'OUT_FOR_DELIVERY'))
            ->latest('generated_at')
            ->first();

        return $this->decrypt($credential);
    }

    public function activeCodeForCustomerNotification(Customer $customer, Notification $notification): ?string
    {
        if (
            $notification->audience !== Notification::AUDIENCE_CUSTOMER
            || (int) $notification->customer_id !== (int) $customer->id
        ) {
            return null;
        }

        $credentialId = $notification->data['delivery_otp_id'] ?? null;

        if (! $credentialId) {
            return null;
        }

        $credential = DeliveryOtp::query()
            ->whereKey($credentialId)
            ->whereNull('used_at')
            ->whereNull('invalidated_at')
            ->where('expires_at', '>', now())
            ->whereHas('order', fn ($query) => $query->where('customer_id', $customer->id))
            ->whereHas('attempt', fn ($query) => $query->where('status', 'OUT_FOR_DELIVERY'))
            ->first();

        return $this->decrypt($credential);
    }

    private function decrypt(?DeliveryOtp $credential): ?string
    {
        if (! $credential?->otp_ciphertext) {
            return null;
        }

        return Crypt::decryptString($credential->otp_ciphertext);
    }
}
