<?php

namespace App\Domains\Marketing\Services;

use App\Models\Customer;
use App\Models\CustomerAnnouncement;
use App\Models\CustomerAnnouncementDismissal;

class CustomerAnnouncementService
{
    public function applicableFor(?Customer $customer): ?CustomerAnnouncement
    {
        if (! $customer) {
            return null;
        }

        return CustomerAnnouncement::query()
            ->current()
            ->where(function ($query) use ($customer) {
                $query->where('audience_type', 'all')
                    ->orWhere(function ($query) use ($customer) {
                        $query->whereIn('audience_type', ['store', 'stores'])
                            ->whereHas('stores', fn ($query) => $query->whereKey($customer->assigned_store_id));
                    })
                    ->orWhere(function ($query) use ($customer) {
                        $query->whereIn('audience_type', ['customer', 'customers'])
                            ->whereHas('customers', fn ($query) => $query->whereKey($customer->id));
                    });
            })
            ->whereDoesntHave('dismissals', fn ($query) => $query->where('customer_id', $customer->id))
            ->orderByDesc('sticky')
            ->orderByDesc('priority')
            ->orderByDesc('created_at')
            ->first();
    }

    public function dismiss(CustomerAnnouncement $announcement, Customer $customer): void
    {
        if (! $announcement->dismissible) {
            return;
        }

        CustomerAnnouncementDismissal::query()->firstOrCreate([
            'customer_announcement_id' => $announcement->id,
            'customer_id' => $customer->id,
        ], [
            'dismissed_at' => now(config('app.timezone')),
        ]);
    }
}
