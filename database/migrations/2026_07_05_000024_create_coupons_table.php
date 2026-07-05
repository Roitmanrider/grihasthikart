<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('discount_type');
            $table->decimal('discount_value', 12, 2);
            $table->decimal('max_discount_amount', 12, 2)->nullable();
            $table->decimal('minimum_order_amount', 12, 2)->default(0);
            $table->unsignedInteger('usage_limit_total')->nullable();
            $table->unsignedInteger('usage_limit_per_customer')->nullable();
            $table->unsignedInteger('usage_limit_per_session')->nullable();
            $table->foreignIdFor(Customer::class)->nullable()->constrained()->nullOnDelete();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->boolean('status')->default(true)->index();
            $table->boolean('is_cashback_coupon')->default(false)->index();
            $table->string('source')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignIdFor(User::class, 'updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
