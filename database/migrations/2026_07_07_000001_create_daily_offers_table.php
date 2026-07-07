<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants')->restrictOnDelete();
            $table->string('title')->nullable();
            $table->decimal('offer_price', 10, 2);
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('display_order')->default(0)->index();
            $table->unsignedInteger('max_quantity_per_order')->nullable();
            $table->string('badge_text')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('product_variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_offers');
    }
};
