<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = User::where('email', $request->email)
                    ->with(['store', 'roles']) // Load Store and Roles
                    ->firstOrFail();

        // Create Token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
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
