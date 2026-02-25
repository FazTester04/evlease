<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $fillable = ['name', 'driver_license', 'phone', 'email'];

    public function leases()
    {
        return $this->hasMany(Lease::class);
    }
}