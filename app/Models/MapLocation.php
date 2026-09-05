<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * MASTER_SCHEMA.md §16 — Module 5, owner Ahmed Safhaan.
 *
 * One row = one clickable marker on the static island map image.
 * pos_x / pos_y are percentages of the image (0–100), not pixels.
 */
class MapLocation extends Model
{
    /**
     * Columns a form is allowed to fill in. Anything not listed here cannot be
     * set by MapLocation::create($request->validated()) — that is Laravel's
     * protection against a hand-crafted form posting extra fields.
     */
    protected $fillable = [
        'name',
        'category',
        'description',
        'pos_x',
        'pos_y',
        'is_visible',
    ];

    /**
     * Turn raw database strings into useful PHP types when they are read.
     * Without this, is_visible comes back as the string "1" rather than true,
     * and @if ($loc->is_visible) would behave oddly.
     */
    protected function casts(): array
    {
        return [
            'pos_x' => 'decimal:2',
            'pos_y' => 'decimal:2',
            'is_visible' => 'boolean',
        ];
    }

    /**
     * Module 4 (Malaaz) reads this: activities that happen at this location.
     * BUILD_CONTRACT.md §6 seam 3 — he reads, Module 5 owns.
     *
     * Commented out until App\Models\ParkActivity exists. Uncomment the moment
     * Malaaz commits it; leaving it live now would fatal the moment anything
     * called it, and there is nothing to gain from that.
     *
     * public function activities(): HasMany
     * {
     *     return $this->hasMany(ParkActivity::class);
     * }
     */

    /**
     * Query scope. Lets a controller write MapLocation::visible()->get()
     * instead of repeating the where() clause on every page that shows the map.
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }
}
