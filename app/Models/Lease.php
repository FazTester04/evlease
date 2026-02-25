<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'monthly_payment' => 'decimal:2',
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
}