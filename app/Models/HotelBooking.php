<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HotelBooking extends Model
{
    protected $fillable = [
        'user_id',
        'hotel_id',
        'reference',
        'check_in',
        'check_out',
        'guests',
        'total_amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'guests' => 'integer',
            'total_amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function bookingRooms(): HasMany
    {
        return $this->hasMany(HotelBookingRoom::class);
    }

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'hotel_booking_rooms')
            ->withPivot('nightly_rate', 'nights')
            ->withTimestamps();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Scope for cross-module reporting (seam 5 — BUILD_CONTRACT.md §6).
     * Admin and Safhaan's analytics read bookings through this scope only;
     * they never write raw queries against hotel_bookings directly.
     *
     * @param  Builder<HotelBooking>  $query
     */
    public function scopeReportable(Builder $query, string $from, string $to): void
    {
        $query->whereBetween('check_in', [$from, $to]);
    }
}
