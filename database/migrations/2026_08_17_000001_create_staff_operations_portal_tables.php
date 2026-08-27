<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('staff_roles')->nullable()->after('role');
            $table->json('additional_permissions')->nullable()->after('staff_roles');
            $table->json('denied_permissions')->nullable()->after('additional_permissions');
            $table->boolean('staff_active')->default(true)->index()->after('denied_permissions');
            $table->timestamp('staff_approved_at')->nullable()->after('staff_active');
            $table->foreignId('staff_approved_by')->nullable()->after('staff_approved_at')->constrained('users')->nullOnDelete();
            $table->index(['assigned_store_id', 'staff_active']);
        });

        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('landmark');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->unsignedInteger('geofence_radius_meters')->nullable()->after('longitude');
        });

        Schema::create('staff_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipient_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('stock_location_id')->nullable()->constrained('stock_locations')->nullOnDelete();
            $table->string('workstream', 40)->index();
            $table->string('event_type', 80)->index();
            $table->nullableMorphs('related');
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('action_url')->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index(['recipient_user_id', 'workstream', 'read_at'], 'staff_notif_user_stream_read_idx');
            $table->index(['stock_location_id', 'workstream', 'created_at'], 'staff_notif_store_stream_created_idx');
        });

        Schema::create('order_staff_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('stock_location_id')->nullable()->constrained('stock_locations')->nullOnDelete();
            $table->string('task_type', 30);
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->string('status', 30)->default('PENDING')->index();
            $table->foreignId('reassigned_from_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reassigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reassigned_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'task_type'], 'order_staff_task_unique');
            $table->index(['stock_location_id', 'task_type', 'status'], 'order_staff_store_type_status_idx');
            $table->index(['assigned_user_id', 'status'], 'order_staff_assignee_status_idx');
        });

        Schema::create('delivery_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('order_staff_assignment_id')->nullable()->constrained('order_staff_assignments')->nullOnDelete();
            $table->foreignId('stock_location_id')->nullable()->constrained('stock_locations')->nullOnDelete();
            $table->foreignId('delivery_agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('attempt_number')->default(1);
            $table->string('status', 40)->default('ASSIGNED')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('invalidated_at')->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'attempt_number'], 'delivery_attempt_order_number_unique');
            $table->index(['delivery_agent_id', 'status'], 'delivery_attempt_agent_status_idx');
            $table->index(['stock_location_id', 'status'], 'delivery_attempt_store_status_idx');
        });

        Schema::create('delivery_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('delivery_attempt_id')->constrained('delivery_attempts')->cascadeOnDelete();
            $table->string('otp_hash');
            $table->text('otp_ciphertext')->nullable();
            $table->timestamp('generated_at')->index();
            $table->timestamp('expires_at')->index();
            $table->timestamp('used_at')->nullable()->index();
            $table->timestamp('invalidated_at')->nullable()->index();
            $table->unsignedTinyInteger('failed_attempt_count')->default(0);
            $table->unsignedBigInteger('active_delivery_attempt_id')->virtualAs('case when `used_at` is null and `invalidated_at` is null then `delivery_attempt_id` else null end');
            $table->timestamps();

            $table->unique('active_delivery_attempt_id', 'delivery_otp_one_active_attempt_unique');
            $table->index(['order_id', 'expires_at']);
        });

        Schema::create('delivery_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('delivery_attempt_id')->nullable()->constrained('delivery_attempts')->nullOnDelete();
            $table->foreignId('stock_location_id')->nullable()->constrained('stock_locations')->nullOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 50)->index();
            $table->timestamp('occurred_at')->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('accuracy_meters', 8, 2)->nullable();
            $table->decimal('distance_from_customer_meters', 10, 2)->nullable();
            $table->string('geofence_result', 30)->nullable()->index();
            $table->string('reason_code', 80)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('otp_verified')->default(false);
            $table->boolean('otp_override_approved')->default(false);
            $table->foreignId('override_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('manager_review_required')->default(false)->index();
            $table->string('review_status', 30)->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'event_type', 'occurred_at'], 'delivery_events_order_type_time_idx');
            $table->index(['actor_user_id', 'occurred_at'], 'delivery_events_actor_time_idx');
        });

        Schema::create('staff_approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_location_id')->nullable()->constrained('stock_locations')->nullOnDelete();
            $table->string('approval_type', 60)->index();
            $table->nullableMorphs('subject');
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('requested_at')->index();
            $table->string('status', 30)->default('PENDING')->index();
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_at')->nullable();
            $table->string('reason_code', 80)->nullable();
            $table->text('notes')->nullable();
            $table->json('evidence')->nullable();
            $table->timestamps();

            $table->index(['stock_location_id', 'approval_type', 'status'], 'staff_approvals_store_type_status_idx');
            $table->index(['requested_by', 'status'], 'staff_approvals_requester_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_approval_requests');
        Schema::dropIfExists('delivery_events');
        Schema::dropIfExists('delivery_otps');
        Schema::dropIfExists('delivery_attempts');
        Schema::dropIfExists('order_staff_assignments');
        Schema::dropIfExists('staff_notifications');

        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'geofence_radius_meters']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['staff_approved_by']);
            $table->dropIndex(['assigned_store_id', 'staff_active']);
            $table->dropIndex(['staff_active']);
            $table->dropColumn([
                'staff_roles',
                'additional_permissions',
                'denied_permissions',
                'staff_active',
                'staff_approved_at',
                'staff_approved_by',
            ]);
        });
    }
};
