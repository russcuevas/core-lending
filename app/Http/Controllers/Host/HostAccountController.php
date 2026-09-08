<?php

namespace App\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Collector;

class HostAccountController extends Controller
{
    public function index()
    {
        $admins = User::whereIn('role', ['admin_encoder', 'admin_releasing', 'host'])->latest()->get();
        $collectors = Collector::with('user')->latest()->get();
        $clients = User::where('role', 'client')->with('client.collector.user')->latest()->paginate(20);

        return view('host.accounts.index', compact('admins', 'collectors', 'clients'));
    }

    public function storeStaff(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'nullable|string|unique:users,phone_number',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin_encoder,admin_releasing,collector,host',
            'address' => 'nullable|string',
            'assigned_area' => 'nullable|string',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'address' => $request->address,
            'status' => 'active',
        ]);

        if ($request->role === 'collector') {
            Collector::create([
                'user_id' => $user->id,
                'assigned_area' => $request->assigned_area ?? 'General Area',
                'commission_balance' => 0.00,
                'total_earned_commission' => 0.00,
            ]);
        }

        return back()->with('success', "New user credentials created successfully for {$user->name} ({$user->role})!");
    }

    public function resetPin(Request $request, User $user)
    {
        $request->validate(['pin_code' => 'required|digits:4']);

        $user->update([
            'pin_code' => $request->pin_code,
            'password' => Hash::make($request->pin_code),
        ]);

        return back()->with('success', "PIN code for {$user->name} was reset to {$request->pin_code}!");
    }

    public function toggleStatus(User $user)
    {
        $newStatus = ($user->status === 'active') ? 'inactive' : 'active';
        $user->update(['status' => $newStatus]);

        return back()->with('success', "Account status for {$user->name} updated to {$newStatus}.");
    }
}
