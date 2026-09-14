<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The FerryTicket relation is still deferred — the model exists, but adding it belongs to
 * Module 3. BR-03 allows exactly one of the three targets to be set on any row.
 */
class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'hotel_booking_id',
        'ferry_ticket_id',
        'ticket_id',
        'reference',
        'amount',
        'method',
        'status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Nullable: an anonymous gate sale has a payment but no account behind it. Report by
     * ticket channel rather than by this column (MASTER_SCHEMA.md §14, §17).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hotelBooking(): BelongsTo
    {
        return $this->belongsTo(HotelBooking::class);
    }

    /**
     * The park or beach admission this payment bought. Module 4.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
