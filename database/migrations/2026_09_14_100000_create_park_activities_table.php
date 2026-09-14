<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * MASTER_SCHEMA.md §12
     *
     * The catalogue of things the park offers — rides, shows and beach events. It carries no
     * date: a row here is "Dolphin Show exists", not "Dolphin Show runs on Tuesday". The
     * scheduled instances live in park_events (§13).
     *
     * Beach events are a `type`, not a separate table. The data dictionary template suggested
     * a beach_events table; folding it in avoids two tables with identical columns, which is
     * the duplication the normalisation section exists to remove.
     *
     * default_capacity and base_price are copied onto a new park_events row and may then be
     * overridden per instance, so a sold-out Saturday can be priced differently from a quiet
     * Tuesday without editing the catalogue.
     */
    public function up(): void
    {
        Schema::create('park_activities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->enum('type', ['ride', 'show', 'beach_event'])->default('ride');
            $table->text('description')->nullable();
            // Nullable: an activity can be catalogued before anyone has placed it on the
            // island map, and deleting a location must not delete the activity.
            $table->foreignId('map_location_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('default_capacity');
            $table->decimal('base_price', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('park_activities');
    }
};
