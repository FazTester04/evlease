<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LateFee extends Model
{
    protected $fillable = [
        'lease_payment_id',
        'lease_id',
        'amount',
        'months_overdue',
        'status',
    ];

    public function leasePayment()
    {
        return $this->belongsTo(LeasePayment::class);
    }

    public function lease()
    {
        return $this->belongsTo(Lease::class);
    }
}