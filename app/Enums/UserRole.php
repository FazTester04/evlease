<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case CLIENT = 'client';
    case DRIVER = 'driver'; // Added for EV leasing system
}