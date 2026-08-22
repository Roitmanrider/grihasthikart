<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_variant_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_location_id')->constrained('stock_locations')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->decimal('mrp', 12, 2)->nullable();
            $table->decimal('selling_price', 12, 2);
            $table->timestamp('effective_from')->nullable()->index();
            $table->timestamp('effective_until')->nullable()->index();
            $table->string('source', 40)->default('manual')->index();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('status')->default(true)->index();
            $table->timestamps();

            $table->unique(['stock_location_id', 'product_variant_id'], 'store_variant_prices_store_variant_unique');
            $table->index(['product_variant_id', 'status']);
        });

        Schema::create('store_variant_price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_location_id')->constrained('stock_locations')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->decimal('old_mrp', 12, 2)->nullable();
            $table->decimal('old_selling_price', 12, 2)->nullable();
            $table->decimal('new_mrp', 12, 2)->nullable();
            $table->decimal('new_selling_price', 12, 2);
            $table->string('change_reason')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at')->useCurrent()->index();
            $table->timestamps();

            $table->index(['stock_location_id', 'product_variant_id', 'changed_at'], 'store_price_history_lookup_index');
        });

        Schema::create('store_price_update_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_location_id')->constrained('stock_locations')->cascadeOnDelete();
            $table->string('name');
            $table->string('status', 30)->default('scheduled')->index();
            $table->timestamp('scheduled_for')->nullable()->index();
            $table->timestamp('applied_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('store_price_update_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_price_update_batch_id');
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->decimal('mrp', 12, 2)->nullable();
            $table->decimal('selling_price', 12, 2);
            $table->timestamps();

            $table->foreign('store_price_update_batch_id', 'spubi_batch_id_fk')
                ->references('id')
                ->on('store_price_update_batches')
                ->cascadeOnDelete();
            $table->unique(['store_price_update_batch_id', 'product_variant_id'], 'store_price_batch_items_unique');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('rapid_price_update_enabled')->default(false)->after('status')->index();
        });

        DB::table('categories')
            ->where(function ($query) {
                $query->whereIn('slug', ['fruits-vegetables', 'vegetables-fruits', 'fruits', 'vegetables'])
                    ->orWhere('name', 'like', '%Fruit%')
                    ->orWhere('name', 'like', '%Vegetable%');
            })
            ->update(['rapid_price_update_enabled' => true]);

        Schema::table('homepage_sections', function (Blueprint $table) {
            $table->dropUnique(['section_key']);
            $table->foreignId('stock_location_id')->nullable()->after('id')->constrained('stock_locations')->cascadeOnDelete();
            $table->unsignedBigInteger('homepage_section_store_identity')->virtualAs('COALESCE(stock_location_id, 0)')->after('stock_location_id');
            $table->string('icon_path')->nullable()->after('subtitle');
            $table->unique(['homepage_section_store_identity', 'section_key'], 'homepage_sections_store_section_unique');
            $table->index(['stock_location_id', 'enabled', 'sort_order'], 'homepage_sections_store_enabled_order_index');
        });

        Schema::table('homepage_banners', function (Blueprint $table) {
            $table->foreignId('stock_location_id')->nullable()->after('id')->constrained('stock_locations')->cascadeOnDelete();
            $table->index(['stock_location_id', 'enabled', 'sort_order'], 'homepage_banners_store_enabled_order_index');
        });

        Schema::create('storefront_page_backgrounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_location_id')->nullable()->constrained('stock_locations')->cascadeOnDelete();
            $table->unsignedBigInteger('storefront_background_store_identity')->virtualAs('COALESCE(stock_location_id, 0)');
            $table->string('page_key', 80)->index();
            $table->string('background_path');
            $table->boolean('is_enabled')->default(true)->index();
            $table->decimal('opacity', 4, 2)->default(1);
            $table->string('repeat_mode', 30)->default('no-repeat');
            $table->string('position', 50)->default('center center');
            $table->string('size_mode', 30)->default('cover');
            $table->boolean('enabled')->default(true)->index();
            $table->timestamps();

            $table->unique(['storefront_background_store_identity', 'page_key'], 'storefront_page_backgrounds_store_page_unique');
        });

        Schema::create('customer_announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('message');
            $table->string('audience_type', 40)->default('all')->index();
            $table->boolean('sticky')->default(false)->index();
            $table->boolean('dismissible')->default(true);
            $table->unsignedInteger('priority')->default(0)->index();
            $table->string('cta_text')->nullable();
            $table->string('cta_url')->nullable();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->boolean('enabled')->default(true)->index();
            $table->timestamp('inactive_since')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cleanup_eligible_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_announcement_stock_location', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_announcement_id');
            $table->foreignId('stock_location_id')->constrained('stock_locations')->cascadeOnDelete();
            $table->foreign('customer_announcement_id', 'casl_announcement_id_fk')
                ->references('id')
                ->on('customer_announcements')
                ->cascadeOnDelete();
            $table->unique(['customer_announcement_id', 'stock_location_id'], 'announcement_store_unique');
        });

        Schema::create('customer_announcement_customer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_announcement_id')->constrained('customer_announcements')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->unique(['customer_announcement_id', 'customer_id'], 'announcement_customer_unique');
        });

        Schema::create('customer_announcement_dismissals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_announcement_id');
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->timestamp('dismissed_at')->useCurrent();
            $table->timestamps();

            $table->foreign('customer_announcement_id', 'cad_announcement_id_fk')
                ->references('id')
                ->on('customer_announcements')
                ->cascadeOnDelete();
            $table->unique(['customer_announcement_id', 'customer_id'], 'announcement_dismissals_unique');
        });

        Schema::create('customer_marketing_banners', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->string('image_path');
            $table->string('mobile_image_path')->nullable();
            $table->string('cta_text')->nullable();
            $table->string('cta_url')->nullable();
            $table->unsignedTinyInteger('display_order')->default(0)->index();
            $table->unsignedInteger('priority')->default(0)->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->boolean('enabled')->default(true)->index();
            $table->timestamp('inactive_since')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cleanup_eligible_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_marketing_banner_stock_location', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_marketing_banner_id');
            $table->foreignId('stock_location_id');
            $table->foreign('customer_marketing_banner_id', 'cmbsl_banner_id_fk')
                ->references('id')
                ->on('customer_marketing_banners')
                ->cascadeOnDelete();
            $table->foreign('stock_location_id', 'cmbsl_store_id_fk')
                ->references('id')
                ->on('stock_locations')
                ->cascadeOnDelete();
            $table->unique(['customer_marketing_banner_id', 'stock_location_id'], 'marketing_banner_store_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_marketing_banner_stock_location');
        Schema::dropIfExists('customer_marketing_banners');
        Schema::dropIfExists('customer_announcement_dismissals');
        Schema::dropIfExists('customer_announcement_customer');
        Schema::dropIfExists('customer_announcement_stock_location');
        Schema::dropIfExists('customer_announcements');
        Schema::dropIfExists('storefront_page_backgrounds');

        Schema::table('homepage_banners', function (Blueprint $table) {
            $table->dropIndex('homepage_banners_store_enabled_order_index');
            $table->dropConstrainedForeignId('stock_location_id');
        });

        Schema::table('homepage_sections', function (Blueprint $table) {
            $table->dropIndex('homepage_sections_store_enabled_order_index');
            $table->dropUnique('homepage_sections_store_section_unique');
            $table->dropColumn('homepage_section_store_identity');
            $table->dropConstrainedForeignId('stock_location_id');
            $table->dropColumn('icon_path');
            $table->unique('section_key');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['rapid_price_update_enabled']);
            $table->dropColumn('rapid_price_update_enabled');
        });

        Schema::dropIfExists('store_price_update_batch_items');
        Schema::dropIfExists('store_price_update_batches');
        Schema::dropIfExists('store_variant_price_histories');
        Schema::dropIfExists('store_variant_prices');
    }
};
