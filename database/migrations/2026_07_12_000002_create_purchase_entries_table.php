<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id')->nullable()->index();
            $table->string('purchase_number')->unique();
            $table->string('bill_number')->nullable();
            $table->date('purchase_date');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('gst_total', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2);
            $table->text('notes')->nullable();
            $table->string('status')->default('posted')->index();
            $table->timestamps();
        });

        Schema::create('purchase_entry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_entry_id')->constrained('purchase_entries')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->string('sku')->nullable();
            $table->decimal('quantity', 12, 3);
            $table->decimal('purchase_price', 12, 2);
            $table->decimal('gst_rate', 8, 2)->default(0);
            $table->decimal('gst_amount', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2);
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();

            $table->index('product_variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_entry_items');
        Schema::dropIfExists('purchase_entries');
    }
};
