<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('custom_delivery_rules_enabled')->default(false)->after('category_cashback_threshold_percent');
            $table->decimal('minimum_order_amount_override', 12, 2)->nullable()->after('custom_delivery_rules_enabled');
            $table->decimal('delivery_charge_override', 12, 2)->nullable()->after('minimum_order_amount_override');
            $table->decimal('free_delivery_threshold_override', 12, 2)->nullable()->after('delivery_charge_override');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'custom_delivery_rules_enabled',
                'minimum_order_amount_override',
                'delivery_charge_override',
                'free_delivery_threshold_override',
            ]);
        });
    }
};
