<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * MASTER_SCHEMA.md §5
     * Room availability is derived from hotel_booking_rooms, never stored here.
     */
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->restrictOnDelete();
            $table->foreignId('room_type_id')->constrained()->restrictOnDelete();
            $table->string('room_number', 10);
            $table->unsignedTinyInteger('floor')->nullable();
            $table->enum('status', ['available', 'maintenance', 'out_of_service'])->default('available');
            $table->timestamps();

            // Room number must be unique within a hotel
            $table->unique(['hotel_id', 'room_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
