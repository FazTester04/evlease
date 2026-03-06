<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Enums\UserRole;

class EnsureUserIsDriver
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && $request->user()->role === UserRole::DRIVER) {
            return $next($request);
        }

        abort(403, 'Unauthorized access.');
    }
}