<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_sections', function (Blueprint $table) {
            $table->id();
            $table->string('section_key')->unique();
            $table->string('section_type')->default('static')->index();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->boolean('enabled')->default(true)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->unsignedTinyInteger('desktop_item_limit')->default(8);
            $table->unsignedTinyInteger('mobile_item_limit')->nullable();
            $table->string('source_mode')->nullable();
            $table->foreignId('root_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->boolean('view_all_enabled')->default(true);
            $table->string('view_all_text')->nullable();
            $table->string('view_all_url')->nullable();
            $table->json('configuration')->nullable();
            $table->timestamps();
        });

        Schema::create('homepage_section_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('homepage_section_id')->constrained('homepage_sections')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['homepage_section_id', 'category_id'], 'homepage_section_categories_unique');
            $table->index(['homepage_section_id', 'sort_order'], 'homepage_section_categories_order_index');
        });

        Schema::create('homepage_section_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('homepage_section_id')->constrained('homepage_sections')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['homepage_section_id', 'product_id'], 'homepage_section_products_unique');
            $table->index(['homepage_section_id', 'sort_order'], 'homepage_section_products_order_index');
        });

        Schema::create('homepage_banners', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->string('cta_text')->nullable();
            $table->string('cta_url')->nullable();
            $table->boolean('open_in_new_tab')->default(false);
            $table->string('alt_text')->nullable();
            $table->string('desktop_image_path');
            $table->string('mobile_image_path')->nullable();
            $table->boolean('enabled')->default(true)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('associated_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('image_path')->nullable();
            $table->string('external_url')->nullable();
            $table->string('promo_text')->nullable();
            $table->boolean('enabled')->default(true)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('associated_partners');
        Schema::dropIfExists('homepage_banners');
        Schema::dropIfExists('homepage_section_products');
        Schema::dropIfExists('homepage_section_categories');
        Schema::dropIfExists('homepage_sections');
    }
};
