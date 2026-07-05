<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variant_attribute_value', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained('attributes')->restrictOnDelete();
            $table->foreignId('attribute_value_id')->constrained('attribute_values')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['product_variant_id', 'attribute_id'], 'variant_attribute_unique');
            $table->unique(['product_variant_id', 'attribute_value_id'], 'variant_attribute_value_unique');
            $table->index('attribute_id');
            $table->index('attribute_value_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_attribute_value');
    }
};
