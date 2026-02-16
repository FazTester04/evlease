<?php

namespace App\Enums;

enum CarStatus: string
{
    case AVAILABLE   = 'available';
    case LEASED      = 'leased';      // for long-term leasing
    case MAINTENANCE = 'maintenance';
    case UNAVAILABLE = 'unavailable'; // temporary out of service

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE   => 'Available',
            self::LEASED      => 'Leased',
            self::MAINTENANCE => 'Maintenance',
            self::UNAVAILABLE => 'Unavailable',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::AVAILABLE   => 'The car is ready for leasing.',
            self::LEASED      => 'The car is under a long-term lease agreement.',
            self::MAINTENANCE => 'The car is undergoing mechanical service or repair.',
            self::UNAVAILABLE => 'The car is temporarily out of service (administrative or technical reasons).',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::AVAILABLE   => '#10B981', // Green
            self::LEASED      => '#8B5CF6', // Purple
            self::MAINTENANCE => '#EF4444', // Red
            self::UNAVAILABLE => '#6B7280', // Gray
        };
    }
}