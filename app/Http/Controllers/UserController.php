<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        // Only get users in my store
        return response()->json(User::with('roles')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,manager,cashier'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'store_id' => Auth::user()->store_id
        ]);

        $user->assignRole($request->role);

        return response()->json($user, 201);
    }

    public function destroy(User $user)
    {
        // Security check: cannot delete yourself
        if ($user->id === Auth::id()) {
            return response()->json(['message' => 'Cannot delete your own account'], 403);
        }
        
        // Security check: cannot delete user from another store
        if ($user->store_id !== Auth::user()->store_id) {
            abort(403);
        }

        $user->delete();
        return response()->json(['message' => 'User deleted']);
    }
}
