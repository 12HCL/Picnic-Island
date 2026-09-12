<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $fillable = [
        'hotel_id',
        'room_type_id',
        'room_number',
        'floor',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'floor' => 'integer',
        ];
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function bookingRooms(): HasMany
    {
        return $this->hasMany(HotelBookingRoom::class);
    }

    public function bookings(): BelongsToMany
    {
        return $this->belongsToMany(HotelBooking::class, 'hotel_booking_rooms')
            ->withPivot('nightly_rate', 'nights')
            ->withTimestamps();
    }

    /**
     * True when the room has no overlapping active booking in the given date range.
     * "Active" means pending, confirmed, or checked_in — completed/cancelled do not block.
     * Availability is derived from hotel_booking_rooms (MASTER_SCHEMA.md §5).
     */
    public function isAvailableFor(string $checkIn, string $checkOut): bool
    {
        if ($this->status !== 'available') {
            return false;
        }

        return ! $this->bookingRooms()
            ->whereHas('hotelBooking', function ($q) use ($checkIn, $checkOut) {
                $q->whereIn('status', ['pending', 'confirmed', 'checked_in'])
                  ->where('check_in', '<', $checkOut)
                  ->where('check_out', '>', $checkIn);
            })
            ->exists();
    }
}
