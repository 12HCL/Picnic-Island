<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotelBookingRoom extends Model
{
    protected $table = 'hotel_booking_rooms';

    protected $fillable = [
        'hotel_booking_id',
        'room_id',
        'nightly_rate',
        'nights',
    ];

    protected function casts(): array
    {
        return [
            'nightly_rate' => 'decimal:2',
            'nights' => 'integer',
        ];
    }

    public function hotelBooking(): BelongsTo
    {
        return $this->belongsTo(HotelBooking::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
