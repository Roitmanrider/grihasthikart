<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CartActivityMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_deployed_000002_migration_remains_4_21f_b_schema_only(): void
    {
        $source = file_get_contents(database_path('migrations/2026_08_10_000002_create_pending_orders_and_cart_revision.php'));

        $this->assertStringContainsString('active_customer_identity', $source);
        $this->assertStringContainsString('pending_order_items', $source);
        $this->assertStringNotContainsString('last_activity_at', $source);
        $this->assertStringNotContainsString('customer_cart_risk_monthly', $source);
        $this->assertStringNotContainsString('whatsapp_reminder_due_at', $source);
    }

    public function test_additive_cart_activity_migration_upgrades_schema(): void
    {
        $this->assertTrue(Schema::hasColumns('pending_orders', [
            'anchor_cart_item_id',
            'last_activity_at',
            'anchor_changed_at',
            'anchor_change_count',
            'whatsapp_reminder_due_at',
            'whatsapp_reminder_attempted_at',
            'whatsapp_reminder_status',
            'follow_up_status',
            'scarce_stock_hold',
            'risk_level',
            'cart_value_snapshot',
            'monthly_risk_generated_at',
            'detail_cleanup_eligible_at',
        ]));

        $this->assertTrue(Schema::hasTable('customer_cart_risk_monthly'));
        $this->assertTrue(Schema::hasColumns('customer_cart_risk_monthly', [
            'customer_id',
            'period_month',
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
        ]));
    }
}
