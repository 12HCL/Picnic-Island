<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MASTER_SCHEMA.md §15 — Module 5, owner Ahmed Safhaan.
 *
 * Promotional adverts and offers. There is NO banners table: a homepage banner
 * is simply a promotion with is_published = true that is inside its date window.
 */
class Promotion extends Model
{
    protected $fillable = [
        'title',
        'body',
        'image_path',
        'module',
        'starts_on',
        'ends_on',
        'is_published',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_published' => 'boolean',
        ];
    }

    /**
     * The staff member who created this promotion.
     * belongsTo = "this row points at one row in users", via created_by.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Published AND inside its date window as of today.
     * This is the scope the homepage and every module banner uses:
     *
     *     Promotion::live()->get();
     *
     * Written once here rather than repeated in four controllers.
     */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->whereDate('starts_on', '<=', now())
            ->whereDate('ends_on', '>=', now());
    }

    /**
     * Narrow to one module's promotions, plus the general ones that run everywhere:
     *
     *     Promotion::live()->forModule('hotel')->get();
     *
     * This is the seam with Raafil's "hotel promotions" — he reads through this
     * scope, Module 5 owns the table and its CRUD.
     */
    public function scopeForModule(Builder $query, string $module): Builder
    {
        return $query->whereIn('module', [$module, 'general']);
    }
}
