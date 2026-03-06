<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\LeaseStatus;

class Lease extends Model
{
    protected $fillable = [
        'car_id',
        'driver_id',
        'start_date',
        'end_date',
        'monthly_payment',
        'down_payment',
        'status',
        'interest_rate',
        'late_fee_rate',
        'late_fee_cap',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'monthly_payment' => 'decimal:2',
        'status' => LeaseStatus::class,
    ];

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LeasePayment::class);
    }
    public function lateFees()
{
    return $this->hasMany(LateFee::class);
}
}
