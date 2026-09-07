<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * MASTER_SCHEMA.md §17 — payments.hotel_booking_id FK → hotel_bookings.id.
     *
     * Deferred out of create_payments_table because hotel_bookings did not exist
     * when that migration was written. ferry_ticket_id and ticket_id stay deferred
     * until Naayif's ferry_tickets and Malaaz's tickets tables land — one migration
     * each, added as each module arrives.
     *
     * MySQL-only: SQLite cannot add a foreign key to an existing table with
     * ALTER TABLE, and the test suite runs on SQLite in-memory.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('hotel_booking_id')
                ->references('id')
                ->on('hotel_bookings')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['hotel_booking_id']);
        });
    }
};
