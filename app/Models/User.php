<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Enums\UserRole;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use App\Models\Lease;
use App\Models\Document;
use App\Models\LeasePayment;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
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
        'driver_license',
        'ic_number',
        'phone',
        'date_of_birth',
        'address',
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
            'driver_license' => 'string',
            'date_of_birth' => 'date',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
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
     * Get the payments made by this user (if any).
     */
    public function payments(): HasMany
    {
        return $this->hasMany(LeasePayment::class, 'driver_id');
    }
    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Get the active lease for this driver.
     */
    public function getActiveLeaseAttribute()
    {
        return $this->leases()->where('status', 'active')->first();
    }

    /**
     * Get the driver's license document.
     */
    public function getLicenseDocumentAttribute()
    {
        return $this->documents()->where('type', 'driver_license')->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope a query to only include users with the driver role.
     */
    public function scopeDrivers($query)
    {
        return $query->where('role', UserRole::DRIVER->value);
    }

    /*
    |--------------------------------------------------------------------------
    | Authentication Redirect
    |--------------------------------------------------------------------------
    */

    /**
     * Determine where to redirect authenticated users when they try to access guest pages.
     */
    public function redirectTo()
    {
        if ($this->role === UserRole::ADMIN) {
            return '/ev-dashboard';
        }

        return '/dashboard';
    }
    public function icDocument()
    {
        return $this->hasOne(Document::class, 'driver_id')->where('type', 'ic');
    }
}
