<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('sku', 100)->unique();
            $table->string('barcode', 100)->nullable()->unique();
            $table->string('variant_name');
            $table->string('attribute_signature', 500)->default('default');
            $table->decimal('weight', 10, 3)->nullable();
            $table->string('unit', 30)->nullable();
            $table->decimal('mrp', 12, 2);
            $table->decimal('selling_price', 12, 2);
            $table->decimal('purchase_price', 12, 2)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('status')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['product_id', 'attribute_signature']);
            $table->index('product_id');
            $table->index('status');
            $table->index('is_default');
            $table->index('display_order');
            $table->index(['product_id', 'status', 'display_order']);
            $table->index(['product_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
