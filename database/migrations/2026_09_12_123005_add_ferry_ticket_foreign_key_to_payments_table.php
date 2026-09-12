<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * MASTER_SCHEMA.md §17 — payments.ferry_ticket_id FK → ferry_tickets.id.
     *
     * Deferred out of create_payments_table because ferry_tickets did not exist
     * when that migration was written. ticket_id stays deferred until Malaaz's
     * tickets table lands.
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
            $table->foreign('ferry_ticket_id')
                ->references('id')
                ->on('ferry_tickets')
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
            $table->dropForeign(['ferry_ticket_id']);
        });
    }
};
