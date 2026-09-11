<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * MASTER_SCHEMA.md §8
     * Static reference data — an origin/destination pair created once and reused by
     * every sailing on it. One route has many ferry_schedules (1:M).
     */
    public function up(): void
    {
        Schema::create('ferry_routes', function (Blueprint $table) {
            $table->id();
            $table->string('origin', 100);
            $table->string('destination', 100);
            $table->unsignedSmallInteger('duration_minutes');
            $table->decimal('base_fare', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ferry_routes');
    }
};
