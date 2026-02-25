<?php

namespace App\Models;

use App\Enums\CarColor;
use App\Enums\CarStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use MohamedGaldi\ViltFilepond\Traits\HasFiles;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

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
         'active_lease_id',
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
     * Get the active lease (most recent active lease) as a model instance.
     * (Not a relationship – returns a model or null)
     */
    public function activeLease()
    {
        return $this->leases()->where('status', 'active')->latest('start_date')->first();
    }
 public function getActiveLeaseIdAttribute()
{
    $lease = $this->leases()->where('status', 'active')->first();
    return $lease?->id;
}
    /**
     * Relationship: the driver of the current active lease (via the lease).
     * This can be eager loaded.
     */
    public function currentDriver(): HasOneThrough
    {
        return $this->hasOneThrough(
            User::class,
            Lease::class,
            'car_id',          // Foreign key on Lease table
            'id',              // Foreign key on User table
            'id',              // Local key on Car table
            'driver_id'        // Local key on Lease table
        )->where('leases.status', 'active');
    }

    /**
     * Get the maintenance services for the car.
     */
    public function maintenanceServices(): HasMany
    {
        return $this->hasMany(MaintenanceService::class, 'car_id');
    }

    /**
     * Get the road tax document for the car.
     */
    public function roadTax(): HasOne
    {
        return $this->hasOne(Document::class, 'car_id')->where('type', 'road_tax');
    }

    /**
     * Get the insurance document for the car.
     */
    public function insurance(): HasOne
    {
        return $this->hasOne(Document::class, 'car_id')->where('type', 'insurance');
    }

    /**
     * Get all documents for the car.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'car_id');
    }

    // ------------------------------------------------------------------------
    // COMPUTED ATTRIBUTES FOR THE FLEET TABLE
    // ------------------------------------------------------------------------

    /**
     * Get the current driver from the active lease.
     */
    public function getCurrentDriverAttribute()
    {
        // If the relation is already loaded, use it
        if ($this->relationLoaded('currentDriver')) {
            return $this->getRelation('currentDriver');
        }
        // Fallback: query the active lease's driver
        return $this->activeLease()?->driver;
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
}