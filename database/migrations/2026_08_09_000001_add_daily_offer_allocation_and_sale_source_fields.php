<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_offers', function (Blueprint $table) {
            $table->decimal('allocated_quantity', 12, 3)->default(0)->after('offer_price');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique(['cart_id', 'product_variant_id']);
            $table->string('sale_type', 30)->default('normal')->after('product_variant_id');
            $table->foreignId('daily_offer_id')->nullable()->after('sale_type')->constrained('daily_offers')->nullOnDelete();
            $table->unique(['cart_id', 'product_variant_id', 'sale_type', 'daily_offer_id'], 'cart_items_cart_variant_sale_type_offer_unique');
            $table->index(['sale_type', 'daily_offer_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('sale_type', 30)->default('normal')->after('product_id');
            $table->foreignId('daily_offer_id')->nullable()->after('sale_type')->constrained('daily_offers')->nullOnDelete();
            $table->index(['sale_type', 'daily_offer_id']);
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['sale_type', 'daily_offer_id']);
            $table->dropConstrainedForeignId('daily_offer_id');
            $table->dropColumn('sale_type');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique('cart_items_cart_variant_sale_type_offer_unique');
            $table->dropIndex(['sale_type', 'daily_offer_id']);
            $table->dropConstrainedForeignId('daily_offer_id');
            $table->dropColumn('sale_type');
            $table->unique(['cart_id', 'product_variant_id']);
        });

        Schema::table('daily_offers', function (Blueprint $table) {
            $table->dropColumn('allocated_quantity');
        });
    }
};
