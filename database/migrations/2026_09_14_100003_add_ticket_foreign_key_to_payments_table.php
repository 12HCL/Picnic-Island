<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * MASTER_SCHEMA.md §17 — payments.ticket_id FK → tickets.id.
     *
     * The last of the three deferred targets in create_payments_table, which left all three
     * columns unconstrained because none of the target tables existed yet. Hotel landed
     * first, ferry on 12 September, and this completes the set — BR-03's CHECK constraint
     * now guards three real foreign keys.
     *
     * MySQL-only: SQLite cannot add a foreign key to an existing table with ALTER TABLE,
     * and the test suite runs on SQLite in-memory. Same guard as the ferry migration.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('ticket_id')
                ->references('id')
                ->on('tickets')
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
            $table->dropForeign(['ticket_id']);
        });
    }
};
