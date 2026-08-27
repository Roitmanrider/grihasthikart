<?php

namespace App\Console\Commands;

use App\Models\DeliveryOtp;
use Illuminate\Console\Command;

class CleanupDeliveryOtps extends Command
{
    protected $signature = 'delivery-otps:cleanup {--days=7}';

    protected $description = 'Delete delivery OTP credential rows after their lifecycle ends while retaining delivery audit events.';

    public function handle(): int
    {
        $cutoff = now()->subDays((int) $this->option('days'));

        $deleted = DeliveryOtp::query()
            ->where(function ($query) use ($cutoff) {
                $query
                    ->where(fn ($used) => $used->whereNotNull('used_at')->where('used_at', '<', $cutoff))
                    ->orWhere(fn ($invalidated) => $invalidated->whereNotNull('invalidated_at')->where('invalidated_at', '<', $cutoff))
                    ->orWhere(fn ($expired) => $expired->whereNull('used_at')->whereNull('invalidated_at')->where('expires_at', '<', $cutoff));
            })
            ->delete();

        $this->info("Deleted {$deleted} delivery OTP credential rows.");

        return self::SUCCESS;
    }
}
