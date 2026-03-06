<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage; // <-- add this

class LeasePayment extends Model
{
    protected $fillable = [
        'lease_id',
        'driver_id',
        'due_date',
        'paid_date',
        'amount',
        'status',
        'receipt_path', // <-- add this
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected $appends = ['proof_url']; // <-- add this

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    public function document()
    {
        return $this->hasOne(Document::class, 'lease_payment_id');
    }

  public function getProofUrlAttribute()
{
    if ($this->document && $this->document->file_path) {
        return Storage::url($this->document->file_path);
    }
    if ($this->receipt_path) {
        return Storage::url($this->receipt_path);
    }
    return null;
}
    public function payments()
    {
        return $this->hasMany(LeasePayment::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
    public function lateFees()
{
    return $this->hasMany(LateFee::class);
}
}
