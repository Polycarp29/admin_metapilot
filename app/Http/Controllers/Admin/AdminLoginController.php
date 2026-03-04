<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AdminLoginController extends Controller
{
    public function show()
    {
        return Inertia::render('Admin/Login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $user = Auth::user();

            if (!$user->is_admin) {
                $user->logActivity('security_alert', 'Unauthorized admin login attempt');
                Auth::logout();
                throw ValidationException::withMessages([
                    'email' => 'Access denied. This portal is for administrators only.',
                ]);
            }

            if (!$user->is_active) {
                $user->logActivity('login_failed', 'Attempted login to deactivated admin account');
                Auth::logout();
                throw ValidationException::withMessages([
                    'email' => 'Your account has been deactivated.',
                ]);
            }

            $user->logActivity('admin_login', 'Successful login to admin portal');
            $request->session()->regenerate();

            return redirect()->intended(route('admin.dashboard'));
        }

        // Log failed attempt
        \App\Models\UserActivity::create([
            'activity_type' => 'login_failed',
            'description' => "Failed admin login attempt for email: {$request->email}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        throw ValidationException::withMessages([
            'email' => trans('auth.failed'),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
