<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * MASTER_SCHEMA.md §10
     * BR-02: a ticket may not be issued when seats_taken >= vessels.capacity. seats_taken is
     * a running counter, not a derived value — it is read and incremented inside the same
     * DB::transaction() that issues the ticket, under lockForUpdate() on this row.
     *
     * The column is seats_taken, incremented from 0. Four prose lines elsewhere in the
     * design folder call it seats_available and describe a decrement; the column tables,
     * the data dictionary, BR-02 and all four diagrams agree on seats_taken, so that wins.
     */
    public function up(): void
    {
        Schema::create('ferry_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ferry_route_id')->constrained()->restrictOnDelete();
            $table->foreignId('vessel_id')->constrained()->restrictOnDelete();
            $table->date('departure_date');
            $table->time('departure_time');
            $table->unsignedSmallInteger('seats_taken')->default(0);
            $table->enum('status', ['scheduled', 'departed', 'cancelled'])->default('scheduled');
            $table->timestamps();

            // Named explicitly: the auto-generated name would be 65 characters and MySQL
            // caps identifiers at 64.
            $table->unique(
                ['ferry_route_id', 'departure_date', 'departure_time'],
                'ferry_schedules_route_departure_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ferry_schedules');
    }
};
