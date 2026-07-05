<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('cart_id')->nullable()->constrained('carts')->nullOnDelete();
            $table->string('session_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('customer_name');
            $table->string('customer_mobile', 15);
            $table->string('customer_email')->nullable();
            $table->string('delivery_address_line1');
            $table->string('delivery_address_line2')->nullable();
            $table->string('delivery_city', 100);
            $table->string('delivery_state', 100);
            $table->string('delivery_pincode', 10);
            $table->string('delivery_landmark')->nullable();
            $table->date('delivery_date')->nullable();
            $table->string('delivery_slot')->nullable();
            $table->string('payment_method')->default('cod')->index();
            $table->string('payment_status')->default('pending')->index();
            $table->string('order_status')->default('placed')->index();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('total_mrp', 12, 2);
            $table->decimal('total_savings', 12, 2);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('delivery_charge', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2);
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
