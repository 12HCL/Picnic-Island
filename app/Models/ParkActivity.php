<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParkActivity extends Model
{
    protected $fillable = [
        'name',
        'type',
        'description',
        'map_location_id',
        'default_capacity',
        'base_price',
        'is_active',
    ];

    /**
     * Mirror the column defaults, so an activity built in memory reports its type and
     * visibility before it has been refreshed from the database.
     */
    protected $attributes = [
        'type' => 'ride',
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'default_capacity' => 'integer',
            'base_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Where on the island map this happens. Nullable — an activity can be catalogued
     * before it has been placed on the map.
     */
    public function mapLocation(): BelongsTo
    {
        return $this->belongsTo(MapLocation::class);
    }

    /**
     * The scheduled instances of this activity — the dated, ticketable occurrences.
     */
    public function events(): HasMany
    {
        return $this->hasMany(ParkEvent::class);
    }

    /**
     * Visitors see only active activities; staff listings see everything.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Beach events are a type rather than a separate table (MASTER_SCHEMA.md §12), so the
     * beach listing is this scope rather than its own model.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }
}
