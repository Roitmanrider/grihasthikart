<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->string('low_stock_state', 30)->default('IN_STOCK')->after('target_stock_level')->index();
            $table->timestamp('low_stock_notified_at')->nullable()->after('low_stock_state');
        });
    }

    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn(['low_stock_state', 'low_stock_notified_at']);
        });
    }
};
