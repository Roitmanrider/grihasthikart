<?php

namespace App\Console\Commands;

use App\Domains\Cart\Services\CartActivityRiskService;
use App\Models\PendingOrder;
use Illuminate\Console\Command;

class CleanupCartActivity extends Command
{
    protected $signature = 'cart-activity:cleanup {--chunk=100 : Number of cart activity rows to delete per chunk}';

    protected $description = 'Purge old cart activity detail without touching final order or accounting history.';

    public function handle(CartActivityRiskService $riskService): int
    {
        $chunk = max(1, (int) $this->option('chunk'));
        $deletedConverted = $this->deleteEligible(PendingOrder::STATUS_CONVERTED, now(), $chunk);
        $deletedAbandoned = $this->deleteEligible(PendingOrder::STATUS_NOT_ORDERED, now(), $chunk);
        $deletedRiskMarks = $riskService->purgeOldRiskMarks();

        $this->info('Cart activity cleanup complete. Converted: '.$deletedConverted.'. Abandoned: '.$deletedAbandoned.'. Risk marks: '.$deletedRiskMarks.'.');

        return self::SUCCESS;
    }

    private function deleteEligible(string $status, \DateTimeInterface $cutoff, int $chunk): int
    {
        $deleted = 0;

        PendingOrder::query()
            ->where('status', $status)
            ->whereNotNull('detail_cleanup_eligible_at')
            ->where('detail_cleanup_eligible_at', '<=', $cutoff)
            ->orderBy('id')
            ->chunkById($chunk, function ($activities) use (&$deleted) {
                foreach ($activities as $activity) {
                    $activity->delete();
                    $deleted++;
                }
            });

        return $deleted;
    }
}
