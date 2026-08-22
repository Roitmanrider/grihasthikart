<?php

namespace App\Console\Commands;

use App\Models\CustomerAnnouncement;
use Illuminate\Console\Command;

class CleanupCustomerAnnouncements extends Command
{
    protected $signature = 'announcements:cleanup {--days=15} {--dry-run}';

    protected $description = 'Hard-delete expired or inactive customer announcements after retention.';

    public function handle(): int
    {
        $cutoff = now('Asia/Kolkata')->subDays(max(1, (int) $this->option('days')));
        $query = CustomerAnnouncement::withTrashed()
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
            $query->get()->each(function (CustomerAnnouncement $announcement): void {
                $announcement->stores()->detach();
                $announcement->customers()->detach();
                $announcement->dismissals()->delete();
                $announcement->forceDelete();
            });
        }

        $this->info(($this->option('dry-run') ? 'Would remove ' : 'Removed ').$count.' announcements.');

        return self::SUCCESS;
    }
}
