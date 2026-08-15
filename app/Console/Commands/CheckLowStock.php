<?php

namespace App\Console\Commands;

use App\Domains\Inventory\Services\ReplenishmentService;
use Illuminate\Console\Command;

class CheckLowStock extends Command
{
    protected $signature = 'inventory:check-low-stock';

    protected $description = 'Reconcile inventory replenishment state and low-stock notifications.';

    public function handle(ReplenishmentService $replenishmentService): int
    {
        $created = $replenishmentService->checkTransitions();
        $this->info($created.' replenishment notification(s) created.');

        return self::SUCCESS;
    }
}
