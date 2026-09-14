<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Ticket extends Model
{
    protected $fillable = [
        'user_id',
        'park_event_id',
        'reference',
        'quantity',
        'unit_price',
        'channel',
        'status',
        'sold_by',
        'validated_at',
    ];

    /**
     * Mirror the column defaults. Without these, a ticket returned straight from create()
     * reports a null status until it is refreshed — which renders an empty status badge on
     * the confirmation screen that follows a sale.
     */
    protected $attributes = [
        'quantity' => 1,
        'channel' => 'online',
        'status' => 'valid',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'validated_at' => 'datetime',
        ];
    }

    /**
     * The buyer. Null for a walk-up gate sale (MASTER_SCHEMA.md §14) — every online sale
     * has a user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(ParkEvent::class, 'park_event_id');
    }

    /**
     * The park staff member who made a gate sale. Null for an online purchase — this is
     * where staff accountability for anonymous sales lives, never payments.user_id.
     */
    public function soldBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sold_by');
    }

    /**
     * The payment that created this ticket. One row, written in the same transaction —
     * BR-03 allows a payments row exactly one target.
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * What the buyer was charged. Derived from the stored unit price, so repricing an
     * event never rewrites a past sale.
     */
    public function total(): string
    {
        return number_format((float) $this->unit_price * $this->quantity, 2, '.', '');
    }

    /**
     * UC-16: a ticket may be admitted once, and only while it is valid.
     */
    public function isAdmissible(): bool
    {
        return $this->status === 'valid';
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->where('status', 'valid');
    }

    /**
     * Revenue reports group by channel, not by user_id, because gate sales have no user.
     */
    public function scopeChannel(Builder $query, string $channel): Builder
    {
        return $query->where('channel', $channel);
    }
}
