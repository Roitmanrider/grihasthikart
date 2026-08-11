<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_orders', function (Blueprint $table) {
            $table->foreignId('anchor_cart_item_id')->nullable()->after('cart_id')->constrained('cart_items')->nullOnDelete();
            $table->timestamp('last_activity_at')->nullable()->after('started_at')->index();
            $table->timestamp('anchor_changed_at')->nullable()->after('expires_at');
            $table->unsignedInteger('anchor_change_count')->default(0)->after('anchor_changed_at');
            $table->timestamp('whatsapp_reminder_due_at')->nullable()->after('reminder_sent_at')->index();
            $table->timestamp('whatsapp_reminder_attempted_at')->nullable()->after('whatsapp_reminder_due_at');
            $table->string('whatsapp_reminder_status', 30)->nullable()->after('whatsapp_reminder_attempted_at')->index();
            $table->string('whatsapp_provider_message_id')->nullable()->after('whatsapp_reminder_status');
            $table->string('whatsapp_failure_code', 80)->nullable()->after('whatsapp_provider_message_id');
            $table->string('whatsapp_failure_message', 255)->nullable()->after('whatsapp_failure_code');
            $table->string('follow_up_status', 30)->default('NOT_CONTACTED')->after('whatsapp_failure_message')->index();
            $table->timestamp('follow_up_updated_at')->nullable()->after('follow_up_status');
            $table->boolean('scarce_stock_hold')->default(false)->after('follow_up_updated_at')->index();
            $table->string('risk_level', 30)->default('NORMAL')->after('scarce_stock_hold')->index();
            $table->decimal('cart_value_snapshot', 12, 2)->default(0)->after('risk_level');
            $table->unsignedInteger('item_count_snapshot')->default(0)->after('cart_value_snapshot');
            $table->unsignedInteger('reserved_sku_count_snapshot')->default(0)->after('item_count_snapshot');
            $table->timestamp('monthly_risk_generated_at')->nullable()->after('reserved_sku_count_snapshot')->index();
            $table->timestamp('detail_cleanup_eligible_at')->nullable()->after('monthly_risk_generated_at')->index();

            $table->index(['status', 'last_activity_at']);
            $table->index(['status', 'whatsapp_reminder_due_at']);
            $table->index(['scarce_stock_hold', 'risk_level']);
        });

        Schema::create('customer_cart_risk_monthly', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->date('period_month');
            $table->string('risk_level', 30)->default('NORMAL')->index();
            $table->unsignedInteger('risk_score')->nullable();
            $table->unsignedInteger('cart_sessions')->default(0);
            $table->unsignedInteger('converted_count')->default(0);
            $table->unsignedInteger('abandoned_count')->default(0);
            $table->unsignedInteger('expired_count')->default(0);
            $table->unsignedInteger('scarce_stock_hold_count')->default(0);
            $table->unsignedInteger('anchor_change_count')->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->timestamp('generated_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['customer_id', 'period_month'], 'customer_cart_risk_month_unique');
            $table->index(['period_month', 'risk_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_cart_risk_monthly');

        Schema::table('pending_orders', function (Blueprint $table) {
            $table->dropIndex(['status', 'last_activity_at']);
            $table->dropIndex(['status', 'whatsapp_reminder_due_at']);
            $table->dropIndex(['scarce_stock_hold', 'risk_level']);
            $table->dropIndex(['last_activity_at']);
            $table->dropIndex(['whatsapp_reminder_due_at']);
            $table->dropIndex(['whatsapp_reminder_status']);
            $table->dropIndex(['follow_up_status']);
            $table->dropIndex(['scarce_stock_hold']);
            $table->dropIndex(['risk_level']);
            $table->dropIndex(['monthly_risk_generated_at']);
            $table->dropIndex(['detail_cleanup_eligible_at']);
            $table->dropConstrainedForeignId('anchor_cart_item_id');
            $table->dropColumn([
                'last_activity_at',
                'anchor_changed_at',
                'anchor_change_count',
                'whatsapp_reminder_due_at',
                'whatsapp_reminder_attempted_at',
                'whatsapp_reminder_status',
                'whatsapp_provider_message_id',
                'whatsapp_failure_code',
                'whatsapp_failure_message',
                'follow_up_status',
                'follow_up_updated_at',
                'scarce_stock_hold',
                'risk_level',
                'cart_value_snapshot',
                'item_count_snapshot',
                'reserved_sku_count_snapshot',
                'monthly_risk_generated_at',
                'detail_cleanup_eligible_at',
            ]);
        });
    }
};
