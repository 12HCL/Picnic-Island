<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FerryRoute extends Model
{
    protected $fillable = [
        'origin',
        'destination',
        'duration_minutes',
        'base_fare',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'base_fare' => 'decimal:2',
        ];
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(FerrySchedule::class);
    }
}
