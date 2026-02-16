<?php

namespace App\Models;

use App\Enums\CarColor;
use App\Enums\CarStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use MohamedGaldi\ViltFilepond\Traits\HasFiles;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Car extends Model
{
    use SoftDeletes;
    use HasFiles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'make',
        'model',
        'year',
        'license_plate',
        'vin',
        'color',
        'description',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'year' => 'integer',
        'status' => CarStatus::class,
        'color' => CarColor::class,
        'vin' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'image_url',
        'full_name',
        'current_driver',
        'payment_status',
        'service_status',
        'next_payment_due',
        'next_service_date',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'deleted_at',
    ];

    // ------------------------------------------------------------------------
    // FILE HANDLING (from original project)
    // ------------------------------------------------------------------------

    /**
     * Get the car image URL.
     */
    public function getImageUrlAttribute(): string
    {
        $file = null;
        if ($this->relationLoaded('files')) {
            $file = $this->files->firstWhere('collection', 'image');
        }

        if (!$file) {
            $file = $this->files()->where('collection', 'image')->first();
        }

        if ($file && $file->path) {
            return Storage::url($file->path);
        }

        return asset('images/car-default.jpg');
    }

    /**
     * Get the full car name (make + model + year).
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->year} {$this->make} {$this->model}";
    }

    // ------------------------------------------------------------------------
    // LEASING RELATIONSHIPS
    // ------------------------------------------------------------------------

    /**
     * Get the leases for the car.
     */
    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class, 'car_id');
    }

    /**
     * Get the active lease (most recent active lease).
     */
    public function activeLease()
    {
        return $this->leases()->where('status', 'active')->latest('start_date')->first();
    }

    /**
     * Get the maintenance services for the car.
     */
    public function maintenanceServices(): HasMany
    {
        return $this->hasMany(MaintenanceService::class, 'car_id');
    }

    // ------------------------------------------------------------------------
    // COMPUTED ATTRIBUTES FOR THE FLEET TABLE
    // ------------------------------------------------------------------------

    /**
     * Get the current driver from the active lease.
     */
    public function getCurrentDriverAttribute()
    {
        $lease = $this->activeLease();
        return $lease?->driver;
    }

    /**
     * Get the payment status for the active lease.
     */
    public function getPaymentStatusAttribute(): string
    {
        $lease = $this->activeLease();
        if (!$lease) {
            return 'N/A';
        }

        $lastPayment = $lease->payments()->latest('due_date')->first();
        if (!$lastPayment) {
            return 'No Payment';
        }

        if ($lastPayment->status === 'paid') {
            return 'Paid on Time';
        }

        if ($lastPayment->status === 'pending') {
            return $lastPayment->due_date->isPast() ? 'Overdue' : 'Pending';
        }

        return ucfirst($lastPayment->status);
    }

    /**
     * Get the next payment due date.
     */
    public function getNextPaymentDueAttribute()
    {
        $lease = $this->activeLease();
        if (!$lease) {
            return null;
        }

        $nextPayment = $lease->payments()
            ->where('status', 'pending')
            ->where('due_date', '>=', now())
            ->orderBy('due_date')
            ->first();

        return $nextPayment?->due_date;
    }

    /**
     * Get the service status based on maintenance records.
     */
    public function getServiceStatusAttribute(): string
    {
        $overdue = $this->maintenanceServices()
            ->whereIn('status', ['overdue'])
            ->orWhere(function ($q) {
                $q->where('status', 'scheduled')
                  ->where('scheduled_date', '<', now());
            })
            ->exists();

        if ($overdue) {
            return 'Overdue';
        }

        $dueSoon = $this->maintenanceServices()
            ->where('status', 'scheduled')
            ->where('scheduled_date', '>=', now())
            ->where('scheduled_date', '<=', now()->addDays(30))
            ->exists();

        if ($dueSoon) {
            return 'Due Soon';
        }

        return 'Up to Date';
    }

    /**
     * Get the next scheduled service date.
     */
    public function getNextServiceDateAttribute()
    {
        $next = $this->maintenanceServices()
            ->where('status', 'scheduled')
            ->where('scheduled_date', '>=', now())
            ->orderBy('scheduled_date')
            ->first();

        return $next?->scheduled_date;
    }
    public function documents(): HasMany
{
    return $this->hasMany(Document::class, 'car_id');
}
}