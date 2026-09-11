<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FerrySchedule extends Model
{
    protected $fillable = [
        'ferry_route_id',
        'vessel_id',
        'departure_date',
        'departure_time',
        'seats_taken',
        'status',
    ];

    /**
     * Mirrors the column default, so a schedule that has not been refreshed from the
     * database still reports a seat count rather than null.
     */
    protected $attributes = [
        'seats_taken' => 0,
    ];

    protected function casts(): array
    {
        return [
            'departure_date' => 'date',
            'seats_taken' => 'integer',
        ];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(FerryRoute::class, 'ferry_route_id');
    }

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(FerryTicket::class);
    }

    /**
     * Seats still sellable on this sailing. BR-02 compares seats_taken against the
     * vessel's capacity, so this is derived and never stored.
     */
    public function seatsRemaining(): int
    {
        return max(0, $this->vessel->capacity - $this->seats_taken);
    }
}
