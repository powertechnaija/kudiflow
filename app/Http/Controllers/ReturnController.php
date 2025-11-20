<?php

namespace App\Http\Controllers;

use App\Constants\AccountCodes;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\ReturnOrder;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReturnController extends Controller
{
    public function store(Request $request, AccountingService $accounting)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'items' => 'required|array',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'reason' => 'required|string'
        ]);

        $storeId = Auth::user()->store_id;
        $order = Order::with('items')->findOrFail($request->order_id);

        $refundAmount = 0;
        $costReversal = 0;

        $return = ReturnOrder::create([
            'store_id' => $storeId,
            'order_id' => $order->id,
            'reason' => $request->reason,
            'status' => 'completed',
        ]);

        foreach ($request->items as $item) {
            $originalItem = $order->items()->where('product_variant_id', $item['variant_id'])->firstOrFail();
            
            $refundAmount += $originalItem->price * $item['quantity'];
            $costReversal += $originalItem->cost_price * $item['quantity'];

            // Restock
            ProductVariant::find($item['variant_id'])->increment('stock_quantity', $item['quantity']);

            $return->items()->create([
                'store_id' => $storeId,
                'product_variant_id' => $item['variant_id'],
                'quantity' => $item['quantity'],
            ]);
        }

        // Resolve Account IDs
        $cashId = $accounting->getAccountId($storeId, AccountCodes::CASH);
        $salesReturnsId = $accounting->getAccountId($storeId, AccountCodes::SALES_RETURNS_AND_ALLOWANCES);
        $inventoryId = $accounting->getAccountId($storeId, AccountCodes::INVENTORY_ASSET);
        $cogsId = $accounting->getAccountId($storeId, AccountCodes::COGS);

        $accounting->recordEntry(
            $storeId,
            now(),
            "Return for Order #{$order->invoice_number}",
            "RET-{$order->id}",
            [
                // Refund Leg (Debit Returns, Credit Cash)
                ['chart_of_account_id' => $salesReturnsId, 'debit' => $refundAmount, 'credit' => 0],
                ['chart_of_account_id' => $cashId, 'debit' => 0, 'credit' => $refundAmount],
                
                // Stock Leg (Debit Inventory, Credit COGS)
                ['chart_of_account_id' => $inventoryId, 'debit' => $costReversal, 'credit' => 0],
                ['chart_of_account_id' => $cogsId, 'debit' => 0, 'credit' => $costReversal],
            ]
        );

        return response()->json(['message' => 'Return processed successfully', 'return_id' => $return->id], 201);
    }
}
