<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained('carts')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('mrp', 12, 2);
            $table->string('product_name_snapshot');
            $table->string('variant_name_snapshot');
            $table->string('sku_snapshot');
            $table->string('hsn_code_snapshot')->nullable();
            $table->decimal('gst_rate_snapshot', 8, 2)->nullable();
            $table->json('attributes_snapshot')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['cart_id', 'product_variant_id']);
            $table->index('cart_id');
            $table->index('product_variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
