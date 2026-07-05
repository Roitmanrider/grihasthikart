<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->string('barcode', 100)->nullable();
            $table->string('hsn_code', 50)->nullable();
            $table->decimal('gst_rate', 5, 2)->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('country_of_origin', 100)->nullable();
            $table->string('shelf_life', 100)->nullable();
            $table->unsignedInteger('minimum_order_quantity')->default(1);
            $table->unsignedInteger('maximum_order_quantity')->nullable();
            $table->boolean('returnable')->default(true);
            $table->boolean('cod_available')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_trending')->default(false);
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_new_arrival')->default(false);
            $table->boolean('status')->default(true);
            $table->integer('display_order')->default(0);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('brand_id');
            $table->index('barcode');
            $table->index('hsn_code');
            $table->index('status');
            $table->index('display_order');
            $table->index('is_featured');
            $table->index('is_trending');
            $table->index('is_popular');
            $table->index('is_new_arrival');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
