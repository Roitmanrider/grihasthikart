<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('mobile')->unique();
            $table->string('email')->nullable();
            $table->boolean('status')->default(true)->index();
            $table->boolean('is_premium')->default(false)->index();
            $table->boolean('cashback_enabled')->default(false)->index();
            $table->decimal('monthly_cashback_threshold', 12, 2)->nullable();
            $table->decimal('category_cashback_threshold_percent', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
