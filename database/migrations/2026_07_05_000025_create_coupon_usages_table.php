<?php

use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Coupon::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Order::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(Customer::class)->nullable()->constrained()->nullOnDelete();
            $table->string('session_id')->nullable()->index();
            $table->string('code_snapshot')->index();
            $table->string('discount_type_snapshot');
            $table->decimal('discount_value_snapshot', 12, 2);
            $table->decimal('discount_amount', 12, 2);
            $table->decimal('cart_subtotal_snapshot', 12, 2);
            $table->timestamp('used_at')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('coupon_id');
            $table->index('order_id');
            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
    }
};
