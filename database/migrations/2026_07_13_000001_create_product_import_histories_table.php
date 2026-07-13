<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_import_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('filename');
            $table->unsignedInteger('rows_processed')->default(0);
            $table->unsignedInteger('products_created')->default(0);
            $table->unsignedInteger('products_updated')->default(0);
            $table->unsignedInteger('variants_created')->default(0);
            $table->unsignedInteger('variants_updated')->default(0);
            $table->unsignedInteger('rows_skipped')->default(0);
            $table->unsignedInteger('rows_failed')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->decimal('duration_seconds', 10, 3)->default(0);
            $table->boolean('successful')->default(false);
            $table->string('duplicate_action', 30)->default('update_existing');
            $table->string('error_report_path')->nullable();
            $table->json('summary')->nullable();
            $table->timestamps();

            $table->index(['successful', 'created_at']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_import_histories');
    }
};
