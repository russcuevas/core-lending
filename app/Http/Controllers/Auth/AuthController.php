<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\SystemNotification;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return view('auth.login');
    }

    public function loginStaff(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $user = Auth::user();

            if ($user->status !== 'active') {
                Auth::logout();
                return back()->with('error', 'Your account is pending approval or inactive. Please contact Superadmin.');
            }

            return $this->redirectBasedOnRole($user)->with('success', "Welcome back, {$user->name}!");
        }

        return back()->with('error', 'Invalid email or password. Please try again.')->withInput();
    }

    public function loginClient(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'pin_code' => 'required|string',
        ]);

        $user = User::where('phone_number', $request->phone_number)
            ->where('role', 'client')
            ->first();

        if (!$user) {
            return back()->with('error', 'No client account found with this phone number.')->withInput();
        }

        if ($user->status === 'pending_approval' || $user->status === 'pending') {
            return back()->with('error', 'Your client account is still pending Superadmin approval.');
        }

        if ($user->status === 'inactive' || $user->status === 'rejected') {
            return back()->with('error', 'Your account is currently inactive.');
        }

        // Check PIN code (plain match or hash check)
        $isPinValid = ($user->pin_code === $request->pin_code) || Hash::check($request->pin_code, $user->password);

        if (!$isPinValid) {
            return back()->with('error', 'Incorrect PIN code entered.')->withInput();
        }

        Auth::login($user, true);

        return redirect()->route('client.dashboard')->with('success', "Welcome to Core Lending, {$user->name}!");
    }

    public function requestPinReset(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'email' => 'nullable|email',
        ]);

        $user = User::where('phone_number', $request->phone_number)->first();

        if ($user) {
            SystemNotification::sendNotification(
                null,
                'host',
                'PIN Reset Requested',
                "Client {$user->name} ({$user->phone_number}) requested a PIN reset.",
                'request_alert',
                '/host/accounts'
            );
        }

        return back()->with('success', 'PIN Reset request submitted. An admin/host will review and assist you shortly.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been successfully logged out.');
    }

    public function redirectBasedOnRole(User $user)
    {
        return match ($user->role) {
            'host' => redirect()->route('host.dashboard'),
            'admin_encoder' => redirect()->route('admin.encoder.dashboard'),
            'admin_releasing' => redirect()->route('admin.releasing.dashboard'),
            'collector' => redirect()->route('collector.dashboard'),
            'client' => redirect()->route('client.dashboard'),
            default => redirect()->route('login'),
        };
    }
}
