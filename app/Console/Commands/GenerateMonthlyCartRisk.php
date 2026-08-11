<?php

namespace App\Console\Commands;

use App\Domains\Cart\Services\CartActivityRiskService;
use Illuminate\Console\Command;

class GenerateMonthlyCartRisk extends Command
{
    protected $signature = 'cart-activity:generate-monthly-risk';

    protected $description = 'Generate compact monthly customer cart risk marks.';

    public function handle(CartActivityRiskService $riskService): int
    {
        $summary = $riskService->generateForPreviousMonth();

        if ($summary['skipped'] ?? false) {
            $this->info('Cart abuse monitoring is disabled. Monthly risk generation skipped.');

            return self::SUCCESS;
        }

        $this->info('Monthly cart risk generated for '.$summary['generated'].' customers.');

        return self::SUCCESS;
    }
}
