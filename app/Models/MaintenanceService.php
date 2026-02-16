<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceService extends Model
{
    protected $fillable = [
        'car_id',
        'description',
        'scheduled_date',
        'completed_date',
        'status',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_date' => 'date',
    ];

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }
}