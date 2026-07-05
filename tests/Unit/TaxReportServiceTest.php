<?php

namespace Tests\Unit;

use App\Domains\Report\Services\TaxReportService;
use App\Models\Order;
use App\Models\OrderItem;
use Database\Seeders\BusinessSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxReportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BusinessSettingSeeder::class);
    }

    public function test_summary_uses_snapshot_tax_amounts(): void
    {
        $order = Order::factory()->create([
            'subtotal' => 105,
            'grand_total' => 105,
            'placed_at' => now(),
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'gst_rate_snapshot' => 5,
            'line_subtotal' => 105,
            'line_total' => 105,
            'line_mrp_total' => 105,
            'tax_amount' => 5,
        ]);

        $summary = app(TaxReportService::class)->gstSummary([]);

        $this->assertSame(100.0, $summary['taxable_amount']);
        $this->assertSame(5.0, $summary['total_gst_collected']);
    }
}
