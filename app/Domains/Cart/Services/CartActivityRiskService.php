<?php

namespace App\Domains\Cart\Services;

use App\Domains\Setting\Services\BusinessSettingService;
use App\Models\CustomerCartRiskMonthly;
use App\Models\PendingOrder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CartActivityRiskService
{
    public function __construct(private readonly BusinessSettingService $settings) {}

    public function generateForPreviousMonth(): array
    {
        if (! $this->settings->get('checkout.cart_abuse_monitoring_enabled', true)) {
            return ['generated' => 0, 'skipped' => true];
        }

        $period = now()->subMonthNoOverflow()->startOfMonth();

        return $this->generateForMonth($period);
    }

    public function generateForMonth(Carbon $period): array
    {
        $start = $period->copy()->startOfMonth();
        $end = $period->copy()->endOfMonth();
        $generated = 0;

        PendingOrder::query()
            ->whereBetween('started_at', [$start, $end])
            ->select('customer_id')
            ->distinct()
            ->orderBy('customer_id')
            ->chunk(100, function ($rows) use ($start, $end, &$generated) {
                foreach ($rows as $row) {
                    DB::transaction(function () use ($row, $start, $end, &$generated) {
                        $activities = PendingOrder::query()
                            ->where('customer_id', $row->customer_id)
                            ->whereBetween('started_at', [$start, $end])
                            ->lockForUpdate()
                            ->get();

                        if ($activities->isEmpty()) {
                            return;
                        }

                        $cartSessions = $activities->count();
                        $converted = $activities->where('status', PendingOrder::STATUS_CONVERTED)->count();
                        $expired = $activities->where('close_reason', PendingOrder::CLOSE_EXPIRED)->count();
                        $abandoned = $activities->where('status', PendingOrder::STATUS_NOT_ORDERED)->count();
                        $scarce = $activities->where('scarce_stock_hold', true)->count();
                        $anchorChanges = (int) $activities->sum('anchor_change_count');
                        $conversionRate = $cartSessions > 0 ? round(($converted / $cartSessions) * 100, 2) : 0;

                        [$score, $level] = $this->score($cartSessions, $converted, $abandoned, $expired, $scarce, $anchorChanges, $conversionRate);

                        CustomerCartRiskMonthly::query()->upsert(
                            [[
                                'customer_id' => $row->customer_id,
                                'period_month' => $start->toDateString(),
                                'risk_level' => $level,
                                'risk_score' => $score,
                                'cart_sessions' => $cartSessions,
                                'converted_count' => $converted,
                                'abandoned_count' => $abandoned,
                                'expired_count' => $expired,
                                'scarce_stock_hold_count' => $scarce,
                                'anchor_change_count' => $anchorChanges,
                                'conversion_rate' => $conversionRate,
                                'generated_at' => now(),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]],
                            ['customer_id', 'period_month'],
                            [
                                'risk_level',
                                'risk_score',
                                'cart_sessions',
                                'converted_count',
                                'abandoned_count',
                                'expired_count',
                                'scarce_stock_hold_count',
                                'anchor_change_count',
                                'conversion_rate',
                                'generated_at',
                                'updated_at',
                            ]
                        );

                        PendingOrder::query()
                            ->whereIn('id', $activities->pluck('id'))
                            ->where('status', PendingOrder::STATUS_NOT_ORDERED)
                            ->update([
                                'monthly_risk_generated_at' => now(),
                                'detail_cleanup_eligible_at' => now()->addDays(7),
                            ]);

                        $generated++;
                    });
                }
            });

        return ['generated' => $generated, 'skipped' => false];
    }

    public function purgeOldRiskMarks(): int
    {
        $oldestKept = now()->subMonthsNoOverflow(6)->startOfMonth();

        return CustomerCartRiskMonthly::query()
            ->where('period_month', '<', $oldestKept->toDateString())
            ->delete();
    }

    private function score(int $sessions, int $converted, int $abandoned, int $expired, int $scarce, int $anchorChanges, float $conversionRate): array
    {
        $score = ($abandoned * 20) + ($expired * 25) + ($scarce * 20) + ($anchorChanges * 5);

        if ($sessions >= 3 && $conversionRate < 25) {
            $score += 20;
        }

        if ($converted > 0 && $conversionRate >= 50) {
            $score = max(0, $score - 15);
        }

        $level = match (true) {
            $score >= 80 => 'HIGH_RISK',
            $score >= 35 => 'WATCH',
            default => 'NORMAL',
        };

        return [$score, $level];
    }
}
