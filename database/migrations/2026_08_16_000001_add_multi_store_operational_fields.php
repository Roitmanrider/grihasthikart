<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_locations', function (Blueprint $table) {
            $table->string('manager_name')->nullable()->after('pincode');
            $table->string('phone')->nullable()->after('manager_name');
            $table->string('email')->nullable()->after('phone');
            $table->boolean('accepts_online_orders')->default(true)->after('status')->index();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 40)->nullable()->after('password')->index();
            $table->foreignId('assigned_store_id')->nullable()->after('role')->constrained('stock_locations')->nullOnDelete();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('assigned_store_id')->nullable()->after('is_premium')->constrained('stock_locations')->nullOnDelete();
            $table->index(['assigned_store_id', 'status']);
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->foreignId('stock_location_id')->nullable()->after('customer_id')->constrained('stock_locations')->nullOnDelete();
            $table->index(['stock_location_id', 'status']);
        });

        Schema::table('pending_orders', function (Blueprint $table) {
            $table->foreignId('stock_location_id')->nullable()->after('cart_id')->constrained('stock_locations')->nullOnDelete();
            $table->index(['stock_location_id', 'status']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('stock_location_id')->nullable()->after('cart_id')->constrained('stock_locations')->nullOnDelete();
            $table->string('store_name_snapshot')->nullable()->after('stock_location_id');
            $table->string('store_code_snapshot')->nullable()->after('store_name_snapshot');
            $table->index(['stock_location_id', 'order_status']);
        });

        Schema::table('purchase_entries', function (Blueprint $table) {
            $table->foreignId('stock_location_id')->nullable()->after('supplier_id')->constrained('stock_locations')->nullOnDelete();
            $table->string('store_name_snapshot')->nullable()->after('stock_location_id');
            $table->string('store_code_snapshot')->nullable()->after('store_name_snapshot');
            $table->index(['stock_location_id', 'purchase_date']);
        });

        Schema::table('daily_offers', function (Blueprint $table) {
            $table->foreignId('stock_location_id')->nullable()->after('product_variant_id')->constrained('stock_locations')->nullOnDelete();
            $table->index(['stock_location_id', 'is_active']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('brand_id_snapshot')->nullable()->after('product_id');
            $table->string('brand_name_snapshot')->nullable()->after('brand_id_snapshot');
        });

        $defaultLocationId = DB::table('stock_locations')
            ->where('is_default', true)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->value('id') ?? DB::table('stock_locations')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->value('id');

        if ($defaultLocationId) {
            DB::table('customers')->whereNull('assigned_store_id')->update(['assigned_store_id' => $defaultLocationId]);
            DB::table('carts')->whereNull('stock_location_id')->update(['stock_location_id' => $defaultLocationId]);
            DB::table('pending_orders')->whereNull('stock_location_id')->update(['stock_location_id' => $defaultLocationId]);
            DB::table('orders')->whereNull('stock_location_id')->update(['stock_location_id' => $defaultLocationId]);
            DB::table('purchase_entries')->whereNull('stock_location_id')->update(['stock_location_id' => $defaultLocationId]);
            DB::table('daily_offers')->whereNull('stock_location_id')->update(['stock_location_id' => $defaultLocationId]);
        }
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['brand_id_snapshot', 'brand_name_snapshot']);
        });

        Schema::table('daily_offers', function (Blueprint $table) {
            $table->dropIndex(['stock_location_id', 'is_active']);
            $table->dropConstrainedForeignId('stock_location_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['stock_location_id', 'order_status']);
            $table->dropConstrainedForeignId('stock_location_id');
            $table->dropColumn(['store_name_snapshot', 'store_code_snapshot']);
        });

        Schema::table('purchase_entries', function (Blueprint $table) {
            $table->dropIndex(['stock_location_id', 'purchase_date']);
            $table->dropConstrainedForeignId('stock_location_id');
            $table->dropColumn(['store_name_snapshot', 'store_code_snapshot']);
        });

        Schema::table('pending_orders', function (Blueprint $table) {
            $table->dropIndex(['stock_location_id', 'status']);
            $table->dropConstrainedForeignId('stock_location_id');
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->dropIndex(['stock_location_id', 'status']);
            $table->dropConstrainedForeignId('stock_location_id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['assigned_store_id', 'status']);
            $table->dropConstrainedForeignId('assigned_store_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_store_id');
            $table->dropColumn('role');
        });

        Schema::table('stock_locations', function (Blueprint $table) {
            $table->dropIndex(['accepts_online_orders']);
            $table->dropColumn(['manager_name', 'phone', 'email', 'accepts_online_orders']);
        });
    }
};
