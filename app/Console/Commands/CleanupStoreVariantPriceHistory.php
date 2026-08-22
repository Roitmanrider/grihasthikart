<?php

namespace App\Console\Commands;

use App\Models\StoreVariantPriceHistory;
use Illuminate\Console\Command;

class CleanupStoreVariantPriceHistory extends Command
{
    protected $signature = 'prices:cleanup-history {--days=90} {--dry-run}';

    protected $description = 'Remove compact store price history older than the operational retention window.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now('Asia/Kolkata')->subDays($days);
        $query = StoreVariantPriceHistory::query()->where('changed_at', '<', $cutoff);
        $count = (clone $query)->count();

        if (! $this->option('dry-run')) {
            $query->delete();
        }

        $this->info(($this->option('dry-run') ? 'Would remove ' : 'Removed ').$count.' price history rows.');

        return self::SUCCESS;
    }
}
