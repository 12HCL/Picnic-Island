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
}
