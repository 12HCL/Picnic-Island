<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * MASTER_SCHEMA.md §13
     *
     * A scheduled instance of an activity: what runs, on which day, at what time. This table
     * is what "manage availability of tickets for specific days" in the brief means.
     *
     * BR-06: seats_taken may not exceed capacity. Same pattern as the ferry (BR-02) —
     * seats_taken is a running counter, not a derived value. It is read and incremented
     * inside the same DB::transaction() that writes the ticket, under lockForUpdate() on this
     * row, because checking and then acting is a race.
     *
     * capacity is per instance rather than read from park_activities.default_capacity: a
     * show can be scheduled into a smaller venue, and a past event must keep the capacity it
     * actually ran with or the sales reports change retrospectively.
     */
    public function up(): void
    {
        Schema::create('park_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('park_activity_id')->constrained()->restrictOnDelete();
            $table->date('event_date');
            $table->time('start_time');
            $table->unsignedSmallInteger('capacity');
            $table->unsignedSmallInteger('seats_taken')->default(0);
            $table->decimal('price', 10, 2);
            $table->enum('status', ['scheduled', 'cancelled', 'completed'])->default('scheduled');
            $table->timestamps();

            // One activity cannot run twice at the same time on the same day.
            $table->unique(
                ['park_activity_id', 'event_date', 'start_time'],
                'park_events_activity_schedule_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('park_events');
    }
};
