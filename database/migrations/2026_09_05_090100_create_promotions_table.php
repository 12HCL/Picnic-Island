<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * MASTER_SCHEMA.md §15 — Module 5, owner Ahmed Safhaan.
     *
     * Promotional adverts and offers. There is NO banners table: a homepage banner
     * is a promotion with is_published = true. `module` lets hotel and park staff
     * manage promotions for their own module while the Admin manages all of them.
     *
     * This table holds no map_location_id — the two Module 5 tables are independent.
     */
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->text('body')->nullable();
            $table->string('image_path', 255)->nullable();
            $table->enum('module', ['hotel', 'ferry', 'park', 'general'])->default('general');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('is_published')->default(false);

            // Staff member who created it. RESTRICT so authorship is never orphaned.
            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();

            // Every visitor-facing query filters on all three, and the homepage
            // runs it on every page load.
            $table->index(['is_published', 'starts_on', 'ends_on'], 'promotions_live_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
