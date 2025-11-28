<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        return DB::transaction(function () use ($request) {
            // 1. Create Store
            $store = Store::create(['name' => $request->store_name]);

            // 2. Create User linked to Store
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'store_id' => $store->id
            ]);
            
            // Assign Admin Role
            $user->assignRole('admin');

            // 3. Seed Default Chart of Accounts for this Store
            $this->seedDefaultAccounts($store->id);

            return response()->json(['token' => $user->createToken('api')->plainTextToken]);
        });
    }

    private function seedDefaultAccounts($storeId)
    {
        $defaults = [
            ['code' => '1001', 'name' => 'Cash', 'type' => 'asset'],
            ['code' => '1200', 'name' => 'Inventory Asset', 'type' => 'asset'],
            ['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability'],
            ['code' => '3000', 'name' => 'Owners Equity', 'type' => 'equity'],
            ['code' => '4000', 'name' => 'Sales Revenue', 'type' => 'revenue'],
            ['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => 'expense'],
        ];

        foreach ($defaults as $account) {
            \App\Models\ChartOfAccount::create(array_merge($account, ['store_id' => $storeId]));
        }
    }
    
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'remember' => 'boolean'
        ]);

        // Manually find user and check password for stateless API auth
        $user = User::where('email', $request->email)
                    ->with(['store', 'roles']) // Pre-load relationships
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Revoke all old tokens
        $user->tokens()->delete();

        // Create New Token with dynamic expiration
        $newAccessToken = $user->createToken('auth_token');
        $token = $newAccessToken->accessToken;

        if ($request->boolean('remember')) {
            // "Remember me" - Set a long expiration (1 week)
            $token->expires_at = now()->addWeek();
        } else {
            // Standard session - use default lifetime from config (120 minutes)
            $token->expires_at = now()->addMinutes(config('session.lifetime', 120));
        }
        
        $token->save();

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $newAccessToken->plainTextToken,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->getRoleNames()->first(),
                'store' => $user->store // Frontend needs this for Currency/Name
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load(['store', 'roles']));
    }
}
