<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashback_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('cashback_percent', 5, 2)->default(5);
            $table->decimal('monthly_order_threshold', 12, 2)->default(5000);
            $table->decimal('eligible_category_threshold_percent', 5, 2)->default(50);
            $table->decimal('redemption_multiple', 12, 2)->default(500);
            $table->unsignedInteger('processing_delay_days')->default(2);
            $table->boolean('status')->default(true)->index();
            $table->boolean('is_default')->default(false)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashback_rules');
    }
};
