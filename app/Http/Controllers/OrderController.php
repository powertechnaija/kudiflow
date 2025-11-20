<?php

namespace App\Http\Controllers;

use App\Constants\AccountCodes;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store(Request $request, AccountingService $accounting)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1'
        ]);

        $storeId = Auth::user()->store_id;

        // 1. Create Order
        $order = Order::create([
            'store_id' => $storeId,
            'customer_id' => $request->customer_id,
            'invoice_number' => 'INV-' . strtoupper(uniqid()),
            'status' => 'completed',
            'total_amount' => 0 // calculated below
        ]);

        $totalRevenue = 0;
        $totalCost = 0;

        foreach ($request->items as $item) {
            $variant = ProductVariant::lockForUpdate()->find($item['variant_id']);

            if ($variant->stock_quantity < $item['quantity']) {
                throw new \Exception("Insufficient stock for " . $variant->product->name);
            }

            $variant->decrement('stock_quantity', $item['quantity']);

            $lineTotal = $variant->price * $item['quantity'];
            $lineCost = $variant->cost_price * $item['quantity'];

            $totalRevenue += $lineTotal;
            $totalCost += $lineCost;

            $order->items()->create([
                'store_id' => $storeId,
                'product_variant_id' => $variant->id,
                'quantity' => $item['quantity'],
                'price' => $variant->price,
                'cost_price' => $variant->cost_price,
            ]);
        }

        $order->update(['total_amount' => $totalRevenue]);

        // 2. Resolve Account IDs
        $cashId = $accounting->getAccountId($storeId, AccountCodes::CASH);
        $revenueId = $accounting->getAccountId($storeId, AccountCodes::SALES_REVENUE);
        $cogsId = $accounting->getAccountId($storeId, AccountCodes::COGS);
        $inventoryId = $accounting->getAccountId($storeId, AccountCodes::INVENTORY_ASSET);

        // 3. Record Journal Entry
        $accounting->recordEntry(
            $storeId,
            now(),
            "Sales Order #{$order->invoice_number}",
            $order->invoice_number,
            [
                // Revenue Leg
                ['chart_of_account_id' => $cashId, 'debit' => $totalRevenue, 'credit' => 0],
                ['chart_of_account_id' => $revenueId, 'debit' => 0, 'credit' => $totalRevenue],
                
                // Cost Leg
                ['chart_of_account_id' => $cogsId, 'debit' => $totalCost, 'credit' => 0],
                ['chart_of_account_id' => $inventoryId, 'debit' => 0, 'credit' => $totalCost],
            ]
        );

        return response()->json($order, 201);
    }
}
