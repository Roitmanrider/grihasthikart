<?php

namespace App\Domains\Marketing\Services;

use App\Models\Customer;
use App\Models\CustomerMarketingBanner;

class CustomerMarketingBannerService
{
    public function applicableFor(?Customer $customer, int $limit = 5)
    {
        if (! $customer) {
            return collect();
        }

        return CustomerMarketingBanner::query()
            ->current()
            ->where(function ($query) use ($customer) {
                $query->whereDoesntHave('stores')
                    ->orWhereHas('stores', fn ($query) => $query->whereKey($customer->assigned_store_id));
            })
            ->orderByDesc('priority')
            ->orderBy('display_order')
            ->orderByDesc('created_at')
            ->limit(max(1, min(5, $limit)))
            ->get();
    }
}
