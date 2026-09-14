<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParkEvent extends Model
{
    protected $fillable = [
        'park_activity_id',
        'event_date',
        'start_time',
        'capacity',
        'seats_taken',
        'price',
        'status',
    ];

    /**
     * Mirrors the column default, so an event that has not been refreshed from the
     * database still reports a seat count rather than null.
     */
    protected $attributes = [
        'seats_taken' => 0,
        'status' => 'scheduled',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'capacity' => 'integer',
            'seats_taken' => 'integer',
            'price' => 'decimal:2',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(ParkActivity::class, 'park_activity_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Admissions still sellable. BR-06 compares seats_taken against capacity, so this is
     * derived and never stored.
     */
    public function seatsRemaining(): int
    {
        return max(0, $this->capacity - $this->seats_taken);
    }

    /**
     * BR-06, as a question the sales screens can ask before opening a transaction. It is
     * not the enforcement point — that is the locked read inside TicketController::store,
     * because anything checked outside the transaction can be stale by the time it is used.
     */
    public function hasCapacityFor(int $quantity = 1): bool
    {
        return $this->status === 'scheduled' && $this->seatsRemaining() >= $quantity;
    }

    /**
     * How full the event is, for the capacity monitoring dashboard.
     */
    public function percentFull(): float
    {
        return $this->capacity > 0
            ? round($this->seats_taken / $this->capacity * 100, 1)
            : 0.0;
    }

    /**
     * What a visitor may still buy: scheduled, today or later, and not sold out.
     */
    public function scopeBookable(Builder $query): Builder
    {
        return $query->where('status', 'scheduled')
            ->whereDate('event_date', '>=', now()->toDateString())
            ->whereColumn('seats_taken', '<', 'capacity');
    }

    public function scopeOnDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('event_date', $date);
    }
}
