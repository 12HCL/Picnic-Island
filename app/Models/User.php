<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/*
 * role_id is deliberately NOT mass-assignable. It is the column the whole
 * access-control model rests on, so it is never filled from a request body:
 * a registration form that passed it through would let anyone sign up as
 * admin. Set it explicitly on the model instead - in RegisterController
 * (always the visitor role) and in Admin\UserController (a deliberate
 * admin action). Factories bypass this by design, so seeders still work.
 *
 * is_active is excluded for the same reason: a deactivated user must not be able
 * to reactivate themselves by posting the field back through a profile form. Only
 * Admin\UserController sets it, per UC-17.
 */
#[Fillable(['name', 'email', 'password', 'phone'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The role assigned to this user.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Determine whether the user has one specific role.
     */
    public function hasRole(string $roleName): bool
    {
        return $this->role?->name === $roleName;
    }

    /**
     * Determine whether the user has any role in the supplied list.
     *
     * @param array<int, string> $roleNames
     */
    public function hasAnyRole(array $roleNames): bool
    {
        return in_array($this->role?->name, $roleNames, true);
    }

    /**
     * Hotel bookings placed by this user.
     */
    public function hotelBookings(): HasMany
    {
        return $this->hasMany(HotelBooking::class);
    }
}
