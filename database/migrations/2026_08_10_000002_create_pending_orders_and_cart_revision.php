<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->unsignedInteger('revision')->default(1)->after('expires_at');
            $table->unsignedBigInteger('active_customer_identity')->nullable()->virtualAs("CASE WHEN status = 'active' AND customer_id IS NOT NULL AND deleted_at IS NULL THEN customer_id ELSE NULL END")->after('revision');
            $table->unique('active_customer_identity', 'carts_one_active_per_customer_unique');
            $table->index(['customer_id', 'status']);
        });

        Schema::create('pending_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('cart_id')->constrained('carts')->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->string('status', 30)->default('ACTIVE')->index();
            $table->timestamp('started_at')->index();
            $table->timestamp('expires_at')->index();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->foreignId('converted_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->timestamp('closed_at')->nullable()->index();
            $table->string('close_reason', 80)->nullable();
            $table->unsignedBigInteger('active_cart_identity')->nullable()->virtualAs("CASE WHEN status = 'ACTIVE' THEN cart_id ELSE NULL END");
            $table->timestamps();

            $table->unique('active_cart_identity', 'pending_orders_one_active_per_cart_unique');
            $table->index(['customer_id', 'status']);
            $table->index(['cart_id', 'status']);
            $table->index(['status', 'expires_at']);
        });

        Schema::create('pending_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pending_order_id')->constrained('pending_orders')->cascadeOnDelete();
            $table->foreignId('cart_item_id')->nullable()->constrained('cart_items')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('product_name_snapshot');
            $table->string('variant_name_snapshot')->nullable();
            $table->string('sku_snapshot')->nullable();
            $table->decimal('quantity', 12, 3);
            $table->decimal('price_snapshot', 12, 2);
            $table->string('sale_type', 30)->default('normal');
            $table->foreignId('daily_offer_id')->nullable()->constrained('daily_offers')->nullOnDelete();
            $table->timestamp('added_at')->index();
            $table->timestamp('removed_at')->nullable()->index();
            $table->timestamps();

            $table->index(['pending_order_id', 'removed_at']);
            $table->index(['product_variant_id', 'sale_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_order_items');
        Schema::dropIfExists('pending_orders');

        Schema::table('carts', function (Blueprint $table) {
            $table->dropUnique('carts_one_active_per_customer_unique');
            $table->dropIndex(['customer_id', 'status']);
            $table->dropColumn('active_customer_identity');
            $table->dropColumn('revision');
        });
    }
};
