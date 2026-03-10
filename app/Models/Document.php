<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use App\Models\Lease;

class Document extends Model
{
    protected $fillable = [
        'car_id',
        'driver_id',
        'lease_payment_id',
        'lease_id',
        'name',
        'type',
        'file_path',
        'expiry_date',
    ];

    protected $casts = [
        'expiry_date' => 'date',
    ];
    protected $appends = ['file_url'];

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
    public function leasePayment()
    {
        return $this->belongsTo(LeasePayment::class);
    }
    public function getFileUrlAttribute()
    {
        return $this->file_path ? url('/files/' . $this->file_path) : null;
    }

    // Optional: an accessor to get the car from the lease payment
    public function getCarAttribute()
    {
        return $this->leasePayment?->lease?->car;
    }

    public function getDriverAttribute()
    {
        return $this->leasePayment?->lease?->driver;
    }
    public function lease()
    {
        return $this->belongsTo(Lease::class);
    }
}
