<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        // Uses BelongsToStore trait automatically
        $customers = QueryBuilder::for(Customer::class)
            ->allowedFilters(['name', 'email', 'phone'])
            ->allowedSorts(['name', 'created_at', 'balance'])
            ->paginate($request->get('per_page', 20));

        return response()->json($customers);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
        ]);

        $customer = Customer::create($request->all());

        return response()->json($customer, 201);
    }

    public function show(Customer $customer)
    {
        // Load recent orders for this customer
        return response()->json(
            $customer->load(['orders' => function($q) {
                $q->latest()->limit(5);
            }])
        );
    }
}