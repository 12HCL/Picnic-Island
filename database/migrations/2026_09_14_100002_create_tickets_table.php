<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * MASTER_SCHEMA.md §14
     *
     * Theme park and beach admissions, sold through both channels the brief asks for:
     * `online` by a visitor with an account, and `gate` at the entrance.
     *
     * user_id is nullable so an at-entrance sale can be made to a walk-up buyer with no
     * account (schema question 2, answered 4 September 2026). Staff accountability is not
     * lost — sold_by records which member of park staff made the sale, and revenue reports
     * group by `channel` rather than by user_id.
     *
     * A row is written only at payment confirmation (schema question 1), so status starts at
     * `valid` and there is no pending_payment state: a ticket that exists has been paid for.
     * Voiding is status = cancelled, never a delete — the sales reports must still see it.
     */
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            // Nullable for gate sales; every online sale has a user.
            $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('park_event_id')->constrained()->restrictOnDelete();
            $table->string('reference', 20)->unique();
            $table->unsignedTinyInteger('quantity')->default(1);
            // Price at the time of sale, copied from park_events.price. Stored, not joined:
            // repricing an event must not rewrite what past buyers were charged.
            $table->decimal('unit_price', 10, 2);
            $table->enum('channel', ['online', 'gate'])->default('online');
            $table->enum('status', ['valid', 'used', 'cancelled'])->default('valid');
            // The park staff member who made a gate sale. Null for an online purchase.
            $table->foreignId('sold_by')->nullable()->constrained('users')->nullOnDelete();
            // Set by the on-site validation screen (UC-16). Null until the ticket is used.
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
