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

        $tab = $request->role === 'collector' ? 'collectors' : 'staff';
        return redirect(route('host.accounts.index') . '#' . $tab)->with('success', "New user credentials created successfully for {$user->name} ({$user->role})!");
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string|min:6',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        $tab = $request->input('tab') ?: ($user->role === 'collector' ? 'collectors' : 'staff');
        return redirect(route('host.accounts.index') . '#' . $tab)->with('success', "Password for {$user->name} has been successfully updated!");
    }

    public function resetPin(Request $request, User $user)
    {
        $request->validate(['pin_code' => 'required|digits:4']);

        $user->update([
            'pin_code' => $request->pin_code,
            'password' => Hash::make($request->pin_code),
        ]);

        $tab = $request->input('tab') ?: 'clients';
        return redirect(route('host.accounts.index') . '#' . $tab)->with('success', "PIN code for {$user->name} was reset to {$request->pin_code}!");
    }

    public function toggleStatus(Request $request, User $user)
    {
        $newStatus = ($user->status === 'active') ? 'inactive' : 'active';
        $user->update(['status' => $newStatus]);

        $tab = $request->input('tab') ?: ($user->role === 'client' ? 'clients' : ($user->role === 'collector' ? 'collectors' : 'staff'));
        return redirect(route('host.accounts.index') . '#' . $tab)->with('success', "Account status for {$user->name} updated to {$newStatus}.");
    }

    public function destroy(Request $request, User $user)
    {
        // Protect superadmin from accidental deletion
        if ($user->id === auth()->id() || ($user->role === 'host' && User::where('role', 'host')->count() <= 1)) {
            return back()->with('error', 'The active Host Superadmin account cannot be deleted.');
        }

        $name = $user->name;
        $tab = $request->input('tab') ?: ($user->role === 'client' ? 'clients' : ($user->role === 'collector' ? 'collectors' : 'staff'));

        // If client, clean up related records safely
        if ($user->client) {
            $client = $user->client;
            
            // Delete schedules & payments for all client loans
            foreach ($client->loans as $loan) {
                $loan->schedules()->delete();
                $loan->payments()->delete();
                $loan->delete();
            }
            
            $client->savings()->delete();
            $client->updateRequests()->delete();
            $client->delete();
        }

        // Clean up wallet transactions & notifications
        \App\Models\WalletTransaction::where('user_id', $user->id)->delete();
        \App\Models\SystemNotification::where('user_id', $user->id)->delete();

        // If collector, remove collector record
        if ($user->collector) {
            $user->collector->delete();
        }

        $user->delete();

        return redirect(route('host.accounts.index') . '#' . $tab)->with('success', "Account '{$name}' has been permanently deleted.");
    }
}
