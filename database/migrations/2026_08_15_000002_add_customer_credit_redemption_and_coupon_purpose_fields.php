<?php

use App\Models\Coupon;
use App\Models\Customer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('original_delivery_charge', 12, 2)->default(0)->after('delivery_charge');
            $table->decimal('delivery_discount_total', 12, 2)->default(0)->after('original_delivery_charge');
            $table->decimal('amount_before_customer_credit', 12, 2)->default(0)->after('grand_total');
            $table->decimal('customer_credit_used', 12, 2)->default(0)->after('amount_before_customer_credit');
            $table->string('coupon_purpose_snapshot', 50)->nullable()->after('coupon_code_snapshot');
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->string('purpose', 50)->default('MERCHANDISE')->after('description')->index();
            $table->string('audience', 50)->default('PUBLIC')->after('purpose')->index();
        });

        Schema::create('coupon_customer', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Coupon::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Customer::class)->constrained()->cascadeOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->unique(['coupon_id', 'customer_id']);
            $table->index('customer_id');
        });

        Schema::table('customer_credit_transactions', function (Blueprint $table) {
            $table->string('idempotency_key')->nullable()->after('created_by')->unique('customer_credit_idempotency_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('customer_credit_transactions', function (Blueprint $table) {
            $table->dropUnique('customer_credit_idempotency_key_unique');
            $table->dropColumn('idempotency_key');
        });

        Schema::dropIfExists('coupon_customer');

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn(['purpose', 'audience']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'original_delivery_charge',
                'delivery_discount_total',
                'amount_before_customer_credit',
                'customer_credit_used',
                'coupon_purpose_snapshot',
            ]);
        });
    }
};
