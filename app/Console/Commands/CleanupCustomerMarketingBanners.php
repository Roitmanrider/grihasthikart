<?php

namespace App\Console\Commands;

use App\Models\CustomerMarketingBanner;
use Illuminate\Console\Command;

class CleanupCustomerMarketingBanners extends Command
{
    protected $signature = 'marketing-banners:cleanup {--days=30} {--dry-run}';

    protected $description = 'Hard-delete expired or inactive customer marketing banners after retention.';

    public function handle(): int
    {
        $cutoff = now('Asia/Kolkata')->subDays(max(1, (int) $this->option('days')));
        $query = CustomerMarketingBanner::withTrashed()
            ->where(function ($query) use ($cutoff) {
                $query->where(function ($query) use ($cutoff) {
                    $query->where('enabled', false)
                        ->whereNotNull('inactive_since')
                        ->where('inactive_since', '<=', $cutoff);
                })->orWhere(function ($query) use ($cutoff) {
                    $query->whereNotNull('ends_at')
                        ->where('ends_at', '<=', $cutoff)
                        ->where(function ($query) {
                            $query->whereNull('starts_at')->orWhereColumn('starts_at', '<=', 'ends_at');
                        });
                });
            });

        $count = (clone $query)->count();

        if (! $this->option('dry-run')) {
            $query->get()->each(function (CustomerMarketingBanner $banner): void {
                $banner->stores()->detach();
                $banner->forceDelete();
            });
        }

        $this->info(($this->option('dry-run') ? 'Would remove ' : 'Removed ').$count.' marketing banners.');

        return self::SUCCESS;
    }
}
