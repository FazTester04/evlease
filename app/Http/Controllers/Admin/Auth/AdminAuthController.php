<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AdminAuthController extends Controller
{
    /**
     * Show the admin login form.
     */
    public function create()
    {
        return Inertia::render('Admin/Auth/AdminLogin');
    }

    /**
     * Handle an admin login request.
     */
    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();
            if ($user->role === UserRole::ADMIN) {
                return redirect()->intended(route('admin.cars.index'));
            }

            // Log out non‑admin user and show error
            Auth::logout();
            return back()->withErrors([
                'email' => 'You do not have permission to access the admin area.',
            ]);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }
}