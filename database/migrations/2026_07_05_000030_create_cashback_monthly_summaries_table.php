<?php

use App\Models\Customer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashback_monthly_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Customer::class)->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->decimal('total_delivered_order_amount', 12, 2)->default(0);
            $table->decimal('eligible_category_order_amount', 12, 2)->default(0);
            $table->decimal('coupon_discount_excluded_amount', 12, 2)->default(0);
            $table->decimal('eligible_cashback_base', 12, 2)->default(0);
            $table->decimal('cashback_percent', 5, 2)->default(5);
            $table->decimal('cashback_amount', 12, 2)->default(0);
            $table->string('eligibility_status')->index();
            $table->timestamp('processed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['customer_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashback_monthly_summaries');
    }
};
