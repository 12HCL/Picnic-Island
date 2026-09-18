<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FerryTicket extends Model
{
    protected $fillable = [
        'user_id',
        'ferry_schedule_id',
        'hotel_booking_id',
        'reference',
        'fare',
        'status',
        'issued_by',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'fare' => 'decimal:2',
            'issued_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(FerrySchedule::class, 'ferry_schedule_id');
    }

    /**
     * The hotel booking that authorised this ticket. BR-01 — never nullable.
     */
    public function hotelBooking(): BelongsTo
    {
        return $this->belongsTo(HotelBooking::class);
    }

    /**
     * The ferry operator who issued the ticket at the counter, when it was a counter sale
     * rather than a visitor self-service purchase. Nullable by design.
     */
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * The simulated payment that brought this ticket into existence. A ticket row is
     * written only at payment confirmation (MASTER_SCHEMA.md §11), so in practice this is
     * never null for a ticket that exists.
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}
