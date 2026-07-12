<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_entries', 'cgst_total')) {
                $table->decimal('cgst_total', 12, 2)->default(0)->after('discount_total');
            }

            if (! Schema::hasColumn('purchase_entries', 'sgst_total')) {
                $table->decimal('sgst_total', 12, 2)->default(0)->after('cgst_total');
            }

            if (! Schema::hasColumn('purchase_entries', 'freight_allocation')) {
                $table->decimal('freight_allocation', 12, 2)->default(0)->after('grand_total');
            }
        });

        Schema::table('purchase_entry_items', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_entry_items', 'discount_amount')) {
                $table->decimal('discount_amount', 12, 2)->default(0)->after('purchase_price');
            }

            if (! Schema::hasColumn('purchase_entry_items', 'cgst_rate')) {
                $table->decimal('cgst_rate', 8, 2)->default(0)->after('gst_rate');
            }

            if (! Schema::hasColumn('purchase_entry_items', 'cgst_amount')) {
                $table->decimal('cgst_amount', 12, 2)->default(0)->after('gst_amount');
            }

            if (! Schema::hasColumn('purchase_entry_items', 'sgst_rate')) {
                $table->decimal('sgst_rate', 8, 2)->default(0)->after('cgst_rate');
            }

            if (! Schema::hasColumn('purchase_entry_items', 'sgst_amount')) {
                $table->decimal('sgst_amount', 12, 2)->default(0)->after('cgst_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_entry_items', function (Blueprint $table) {
            foreach (['sgst_amount', 'sgst_rate', 'cgst_amount', 'cgst_rate', 'discount_amount'] as $column) {
                if (Schema::hasColumn('purchase_entry_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('purchase_entries', function (Blueprint $table) {
            foreach (['freight_allocation', 'sgst_total', 'cgst_total'] as $column) {
                if (Schema::hasColumn('purchase_entries', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
