<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->string('approval_status', 30)->default('PENDING')->after('is_approved')->index();
            $table->string('rejection_reason')->nullable()->after('approval_status');
            $table->timestamp('approval_status_changed_at')->nullable()->after('rejection_reason');
        });

        DB::table('customer_addresses')
            ->where('is_approved', true)
            ->update([
                'approval_status' => 'APPROVED',
                'approval_status_changed_at' => DB::raw('COALESCE(updated_at, created_at)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->dropColumn(['approval_status', 'rejection_reason', 'approval_status_changed_at']);
        });
    }
};
