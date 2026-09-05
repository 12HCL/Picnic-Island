<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * MASTER_SCHEMA.md §17
     * BR-03: exactly one booking or ticket target must be non-null.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            // Ferry and park ticket tables are not yet available; a later migration
            // will add all three target foreign keys together, including hotel bookings.
            $table->unsignedBigInteger('hotel_booking_id')->nullable();
            $table->unsignedBigInteger('ferry_ticket_id')->nullable();
            $table->unsignedBigInteger('ticket_id')->nullable();
            $table->string('reference', 20)->unique();
            $table->decimal('amount', 10, 2);
            $table->enum('method', ['card', 'cash', 'transfer'])->default('card');
            $table->enum('status', ['pending', 'paid', 'refunded', 'failed'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        // MySQL-only: SQLite cannot add this constraint with ALTER TABLE.
        // The payment form request must validate BR-03 for SQLite test runs (request pending).
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE payments ADD CONSTRAINT chk_payments_single_target CHECK (((hotel_booking_id IS NOT NULL) + (ferry_ticket_id IS NOT NULL) + (ticket_id IS NOT NULL)) = 1)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
