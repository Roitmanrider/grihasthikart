<?php

namespace App\Console\Commands;

use App\Domains\Cart\Services\PendingOrderService;
use Illuminate\Console\Command;

class ProcessPendingOrders extends Command
{
    protected $signature = 'pending-orders:process {--chunk=100 : Number of pending records to scan per chunk}';

    protected $description = 'Process pending cart reminders and expiries.';

    public function handle(PendingOrderService $pendingOrders): int
    {
        $summary = $pendingOrders->processDue((int) $this->option('chunk'));

        $this->info('Pending orders processed. Reminded: '.$summary['reminded'].'. Expired: '.$summary['expired'].'.');

        return self::SUCCESS;
    }
}
