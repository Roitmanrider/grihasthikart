<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_orders', function (Blueprint $table) {
            $table->timestamp('follow_up_eligible_at')->nullable()->after('follow_up_status')->index();
            $table->foreignId('assigned_admin_user_id')->nullable()->after('follow_up_updated_at')->constrained('users')->nullOnDelete();

            $table->index(['status', 'follow_up_eligible_at']);
            $table->index(['assigned_admin_user_id', 'status']);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->timestamp('daily_offer_reserved_until')->nullable()->after('daily_offer_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropIndex(['daily_offer_reserved_until']);
            $table->dropColumn('daily_offer_reserved_until');
        });

        Schema::table('pending_orders', function (Blueprint $table) {
            $table->dropIndex(['status', 'follow_up_eligible_at']);
            $table->dropIndex(['assigned_admin_user_id', 'status']);
            $table->dropConstrainedForeignId('assigned_admin_user_id');
            $table->dropIndex(['follow_up_eligible_at']);
            $table->dropColumn('follow_up_eligible_at');
        });
    }
};
