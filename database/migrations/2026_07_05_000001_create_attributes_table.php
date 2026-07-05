<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->string('slug')->unique();
            $table->string('type', 30);
            $table->integer('display_order')->default(0);
            $table->boolean('is_filterable')->default(false);
            $table->boolean('is_variant_defining')->default(false);
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('status');
            $table->index('is_filterable');
            $table->index('is_variant_defining');
            $table->index('display_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attributes');
    }
};
