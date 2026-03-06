<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceService extends Model
{
    protected $table = 'maintenance_services';

    protected $fillable = [
        'car_id',
        'driver_id',
        'scheduled_date',
        'completed_date',
        'status',
        'description',
        'notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_date' => 'date',
    ];

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}