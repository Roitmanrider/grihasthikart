<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {

            $table->id();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();

            $table->string('name', 150);

            $table->string('slug')->unique();

            $table->text('description')->nullable();

            $table->string('image')->nullable();

            $table->string('banner')->nullable();

            $table->string('icon')->nullable();

            $table->string('meta_title')->nullable();

            $table->text('meta_description')->nullable();

            $table->text('meta_keywords')->nullable();

            $table->integer('display_order')->default(0);

            $table->boolean('is_featured')->default(false);

            $table->boolean('show_in_menu')->default(true);

            $table->boolean('show_on_homepage')->default(false);

            $table->boolean('status')->default(true);

            $table->unsignedBigInteger('created_by')->nullable();

            $table->unsignedBigInteger('updated_by')->nullable();

            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();

            $table->softDeletes();

            $table->index('parent_id');

            $table->index('display_order');

            $table->index('status');

            $table->index('is_featured');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
