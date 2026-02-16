<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeasePayment extends Model
{
    protected $fillable = [
        'lease_id',
        'due_date',
        'paid_date',
        'amount',
        'status',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }
}