<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the account activation flag.
     * MASTER_SCHEMA.md §2
     *
     * UC-17 deactivates a staff account rather than deleting it, and it has to:
     * hotel_bookings.user_id and payments.user_id are both ON DELETE RESTRICT,
     * so MySQL refuses to delete any user who has ever booked or paid. Deactivation
     * is the only mechanism that works on a real account.
     *
     * Defaulted to true so every existing row stays valid without a data migration.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')
                ->default(true)
                ->after('phone');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
