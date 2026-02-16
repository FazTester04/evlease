<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Enums\UserRole;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'driver_license', // Added for EV leasing system
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'driver_license' => 'string', // Added for EV leasing system
        ];
    }

    /**
     * Get the reservations for the user.
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Get the payments for the user.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the tickets created by the user.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Get the messages sent by the user.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /*
    |--------------------------------------------------------------------------
    | EV Leasing System Relationships & Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Get the leases for this driver.
     */
    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class, 'driver_id');
    }

    /**
     * Get the documents for this driver.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'driver_id');
    }

    /**
     * Scope a query to only include users with the driver role.
     */
    public function scopeDrivers($query)
    {
        return $query->where('role', UserRole::DRIVER->value);
    }
}