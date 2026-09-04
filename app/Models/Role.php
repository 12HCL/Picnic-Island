<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    /**
     * Fields that may be assigned when creating or updating a role.
     */
    protected $fillable = [
        'name',
        'label',
        'description',
    ];

    /**
     * One role can be assigned to many users.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
