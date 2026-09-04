<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * MASTER_SCHEMA.md §7
     * Junction table resolving the M:N between hotel_bookings and rooms.
     * nightly_rate is copied at booking time — a later price change must not rewrite history.
     * This is the repeating group that the normalisation walkthrough starts from.
     */
    public function up(): void
    {
        Schema::create('hotel_booking_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->decimal('nightly_rate', 10, 2);
            $table->unsignedSmallInteger('nights');
            $table->timestamps();

            // A room can only appear once per booking
            $table->unique(['hotel_booking_id', 'room_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_booking_rooms');
    }
};
