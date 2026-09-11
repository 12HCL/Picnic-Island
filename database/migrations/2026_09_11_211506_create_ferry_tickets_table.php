<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * MASTER_SCHEMA.md §11
     *
     * BR-01, the core rule of the brief: a ferry ticket may only be issued to a visitor who
     * already holds a valid hotel booking. hotel_booking_id is NOT NULL on purpose — that is
     * the database layer of the rule. The other two layers are HotelBookingGateway in the
     * service layer and the form request.
     *
     * A row is written only at payment confirmation (schema question 1, answered 4 September
     * 2026), so status starts at `issued` and there is no pending_payment state: a ticket that
     * exists has been paid for.
     */
    public function up(): void
    {
        Schema::create('ferry_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('ferry_schedule_id')->constrained()->restrictOnDelete();
            $table->foreignId('hotel_booking_id')->constrained()->restrictOnDelete();
            $table->string('reference', 20)->unique();
            $table->decimal('fare', 10, 2);
            $table->enum('status', ['issued', 'boarded', 'cancelled'])->default('issued');
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ferry_tickets');
    }
};
