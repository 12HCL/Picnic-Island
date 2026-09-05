<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * MASTER_SCHEMA.md §16 — Module 5, owner Ahmed Safhaan.
     *
     * Points of interest on the static island map image. pos_x and pos_y are
     * PERCENTAGES of the image width and height, not pixels and not lat/long:
     * the Dean explicitly excluded a maps API, and percentages keep the clickable
     * regions correct at any rendered image size.
     *
     * Runs before create_promotions_table and before Module 4's park_activities,
     * which holds a nullable foreign key onto this table (BUILD_CONTRACT.md §6 seam 3).
     */
    public function up(): void
    {
        Schema::create('map_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->enum('category', ['hotel', 'jetty', 'attraction', 'beach', 'facility'])
                ->default('attraction');
            $table->text('description')->nullable();
            $table->decimal('pos_x', 5, 2);
            $table->decimal('pos_y', 5, 2);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('map_locations');
    }
};
