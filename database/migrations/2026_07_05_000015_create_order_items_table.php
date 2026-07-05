<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('product_name_snapshot');
            $table->string('variant_name_snapshot');
            $table->string('sku_snapshot');
            $table->string('barcode_snapshot')->nullable();
            $table->string('hsn_code_snapshot')->nullable();
            $table->decimal('gst_rate_snapshot', 8, 2)->nullable();
            $table->json('attributes_snapshot')->nullable();
            $table->decimal('quantity', 12, 3);
            $table->decimal('mrp', 12, 2);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('line_subtotal', 12, 2);
            $table->decimal('line_mrp_total', 12, 2);
            $table->decimal('line_savings', 12, 2);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();

            $table->index('order_id');
            $table->index('product_variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
