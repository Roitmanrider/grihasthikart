<?php

namespace App\Domains\Customer\Services;

use App\Domains\Notification\Services\NotificationService;
use App\Models\Customer;
use App\Models\CustomerSession;
use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Illuminate\Support\Str;

class CustomerSessionService
{
    public const SESSION_TOKEN_KEY = 'customer_session_token';

    public function __construct(private readonly NotificationService $notificationService) {}

    public function start(Customer $customer, Store $session, ?Request $request = null): CustomerSession
    {
        $token = Str::random(64);
        $now = now();

        $activeSessions = CustomerSession::query()
            ->active()
            ->where('customer_id', $customer->id)
            ->oldest('logged_in_at')
            ->get();

        $revokedOldest = false;

        while ($activeSessions->count() >= 2) {
            $oldest = $activeSessions->shift();
            $oldest?->update(['revoked_at' => $now]);
            $revokedOldest = true;
        }

        $customerSession = CustomerSession::query()->create([
            'customer_id' => $customer->id,
            'session_token_hash' => $this->hashToken($token),
            'device_label' => $this->deviceLabel($request?->userAgent()),
            'ip_address' => $request?->ip(),
            'logged_in_at' => $now,
            'last_seen_at' => $now,
            'expires_at' => $now->copy()->addDays(21),
        ]);

        $session->put(self::SESSION_TOKEN_KEY, $token);

        if ($revokedOldest) {
            $this->notificationService->notifyCustomerNewDeviceSignedIn($customer);
        }

        return $customerSession;
    }

    public function validate(Store $session): ?CustomerSession
    {
        $token = $session->get(self::SESSION_TOKEN_KEY);
        $customerId = $session->get('customer_id');

        if (! $token || ! $customerId) {
            return null;
        }

        $customerSession = CustomerSession::query()
            ->where('customer_id', $customerId)
            ->where('session_token_hash', $this->hashToken($token))
            ->first();

        if (! $customerSession || $customerSession->revoked_at) {
            return null;
        }

        if ($customerSession->expires_at->isPast()) {
            $this->revokeCurrent($session);

            return null;
        }

        $customerSession->update(['last_seen_at' => now()]);

        return $customerSession;
    }

    public function revokeCurrent(Store $session): void
    {
        $token = $session->get(self::SESSION_TOKEN_KEY);

        if ($token) {
            CustomerSession::query()
                ->where('session_token_hash', $this->hashToken($token))
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);
        }

        $session->forget(self::SESSION_TOKEN_KEY);
    }

    public function revokeAllForCustomer(Customer $customer): void
    {
        CustomerSession::query()
            ->where('customer_id', $customer->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function revokeOtherSessions(Customer $customer, Store $session): int
    {
        $token = $session->get(self::SESSION_TOKEN_KEY);

        if (! $token) {
            return 0;
        }

        return CustomerSession::query()
            ->where('customer_id', $customer->id)
            ->where('session_token_hash', '!=', $this->hashToken($token))
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    private function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    private function deviceLabel(?string $userAgent): ?string
    {
        if (! $userAgent) {
            return null;
        }

        return Str::limit($userAgent, 120, '');
    }
}
