<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('stock_location_id')->constrained('stock_locations')->cascadeOnDelete();
            $table->decimal('quantity_on_hand', 12, 3)->default(0);
            $table->decimal('reserved_quantity', 12, 3)->default(0);
            $table->decimal('damaged_quantity', 12, 3)->default(0);
            $table->decimal('low_stock_threshold', 12, 3)->nullable();
            $table->decimal('reorder_level', 12, 3)->nullable();
            $table->decimal('target_stock_level', 12, 3)->nullable();
            $table->boolean('status')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['product_variant_id', 'stock_location_id']);
            $table->index('product_variant_id');
            $table->index('stock_location_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
