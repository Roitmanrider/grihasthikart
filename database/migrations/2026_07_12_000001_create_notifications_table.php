<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('notifiable');
            $table->string('audience')->index();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('type')->index();
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('action_url')->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index(['audience', 'customer_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
