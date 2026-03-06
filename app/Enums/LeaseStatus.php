<?php

namespace App\Enums;

enum LeaseStatus: string
{
    case ACTIVE = 'active';
    case PENDING = 'pending';
    case ENDED = 'ended';
    case PAUSED = 'paused';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::PENDING => 'Pending',
            self::ENDED => 'Ended',
            self::PAUSED => 'Paused',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => '#10B981', // Green
            self::PENDING => '#F59E0B', // Amber
            self::ENDED => '#6B7280', // Gray
            self::PAUSED => '#EF4444', // Red
        };
    }
}