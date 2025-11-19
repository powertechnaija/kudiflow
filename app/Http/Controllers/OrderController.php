<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AccountingService;

class OrderController extends Controller
{
    //
    // OrderController.php


    public function store(Request $request, AccountingService $accounting)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.variant_id' => 'required|exists:product_variants,id', // 'exists' checks scoped DB
            'items.*.quantity' => 'required|integer|min:1'
        ]);

        return DB::transaction(function () use ($request, $accounting) {
            // 1. Create Order
            $order = Order::create([
                'invoice_number' => 'INV-' . strtoupper(uniqid()),
                'status' => 'completed',
                'total_amount' => 0 // calculated below
            ]);

            $totalRevenue = 0;
            $totalCost = 0;

            foreach ($request->items as $item) {
                // 'lockForUpdate' prevents race conditions
                // The BelongsToStore trait ensures we only find OUR variants
                $variant = ProductVariant::lockForUpdate()->find($item['variant_id']);

                if ($variant->stock_quantity < $item['quantity']) {
                    throw new \Exception("Insufficient stock for " . $variant->product->name);
                }

                // Deduct Stock
                $variant->decrement('stock_quantity', $item['quantity']);

                $lineTotal = $variant->price * $item['quantity'];
                $lineCost = $variant->cost_price * $item['quantity'];

                $totalRevenue += $lineTotal;
                $totalCost += $lineCost;

                $order->items()->create([
                    'product_variant_id' => $variant->id,
                    'quantity' => $item['quantity'],
                    'price' => $variant->price,
                    'cost_price' => $variant->cost_price,
                ]);
            }

            $order->update(['total_amount' => $totalRevenue]);

            // 2. Resolve Account IDs specifically for this Store
            $cashId = $accounting->getAccountId(AccountCodes::CASH);
            $revenueId = $accounting->getAccountId(AccountCodes::SALES_REVENUE);
            $cogsId = $accounting->getAccountId(AccountCodes::COGS);
            $inventoryId = $accounting->getAccountId(AccountCodes::INVENTORY_ASSET);

            // 3. Record Journal Entry
            $accounting->recordEntry(
                now(),
                "Sales Order #{$order->invoice_number}",
                $order->invoice_number,
                [
                    // Revenue Leg
                    ['account_id' => $cashId, 'debit' => $totalRevenue, 'credit' => 0],
                    ['account_id' => $revenueId, 'debit' => 0, 'credit' => $totalRevenue],
                    
                    // Cost Leg
                    ['account_id' => $cogsId, 'debit' => $totalCost, 'credit' => 0],
                    ['account_id' => $inventoryId, 'debit' => 0, 'credit' => $totalCost],
                ]
            );

            return response()->json($order);
        });
    }
}
