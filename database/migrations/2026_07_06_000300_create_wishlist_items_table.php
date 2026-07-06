<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('session_id')->nullable();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
            $table->index('session_id');
            $table->index('product_variant_id');
            $table->unique(['customer_id', 'product_variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlist_items');
    }
};
