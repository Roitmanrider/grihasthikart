<?php

use App\Models\Coupon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->foreignIdFor(Coupon::class)->nullable()->after('customer_id')->constrained()->nullOnDelete();
            $table->string('coupon_code')->nullable()->after('coupon_id');
            $table->decimal('coupon_discount_amount', 12, 2)->default(0)->after('coupon_code');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignIdFor(Coupon::class)->nullable()->after('delivery_slot')->constrained()->nullOnDelete();
            $table->string('coupon_code_snapshot')->nullable()->after('coupon_id');
            $table->decimal('coupon_discount_amount', 12, 2)->default(0)->after('coupon_code_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(Coupon::class);
            $table->dropColumn(['coupon_code_snapshot', 'coupon_discount_amount']);
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(Coupon::class);
            $table->dropColumn(['coupon_code', 'coupon_discount_amount']);
        });
    }
};
