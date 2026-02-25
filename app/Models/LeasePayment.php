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
    return $this->document?->file_path ? Storage::url($this->document->file_path) : null;
}
}