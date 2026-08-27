<?php

namespace App\Http\Controllers\Staff;

use App\Domains\Staff\Services\StaffPermissionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffAuthController extends Controller
{
    public function showLogin()
    {
        return view('staff.auth.login');
    }

    public function login(Request $request, StaffPermissionService $permissions)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid staff credentials.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $user = $request->user();

        if (! $permissions->isOperationalStaff($user) || ! $user->staff_active) {
            Auth::logout();

            return back()->withErrors(['email' => 'This staff account is not active for operations.'])->onlyInput('email');
        }

        return redirect()->route('staff.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('staff.login');
    }
}
