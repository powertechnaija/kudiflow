<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class ReturnController extends Controller
{
    public function store(Request $request, AccountingService $accounting)
{
    $request->validate([
        'order_id' => 'required|exists:orders,id',
        'items' => 'required|array',
        'items.*.variant_id' => 'required',
        'items.*.quantity' => 'required|integer|min:1',
        'reason' => 'required|string'
    ]);

    return DB::transaction(function () use ($request, $accounting) {
        $order = Order::with('items')->find($request->order_id);
        
        $refundAmount = 0;
        $costReversalAmount = 0;

        // 1. Create Return Record
        $returnRecord = ReturnOrder::create([
            'order_id' => $order->id,
            'reason' => $request->reason,
            'date' => now(),
        ]);

        foreach ($request->items as $returnItem) {
            // Find original sale price and cost from the OrderItem snapshot
            $originalItem = $order->items()
                ->where('product_variant_id', $returnItem['variant_id'])
                ->firstOrFail();

            // Validation: Cannot return more than bought
            if ($returnItem['quantity'] > $originalItem->quantity) {
                throw new \Exception("Cannot return more items than purchased.");
            }

            // Calculate Financials
            $refundAmount += $originalItem->price * $returnItem['quantity'];
            $costReversalAmount += $originalItem->cost_price * $returnItem['quantity'];

            // RESTOCK INVENTORY
            // Only if the item is resellable (Good condition)
            // If damaged, we would Credit Inventory and Debit "Spoilage Expense" instead.
            // We assume resellable for this example:
            ProductVariant::find($returnItem['variant_id'])
                ->increment('stock_quantity', $returnItem['quantity']);
        }

        // 2. Accounting Entries (Reversal)
        
        $cashId = ChartOfAccount::where('code', AccountCodes::CASH)->first()->id;
        $salesReturnId = ChartOfAccount::where('code', AccountCodes::SALES_RETURNS)->first()->id;
        $inventoryId = ChartOfAccount::where('code', AccountCodes::INVENTORY_ASSET)->first()->id;
        $cogsId = ChartOfAccount::where('code', AccountCodes::COGS)->first()->id;

        $accounting->recordEntry(
            now(),
            "Return for Order #{$order->invoice_number}",
            "RET-{$order->id}",
            [
                // A. REFUND LEG
                // Debit Sales Returns (Reduces Net Sales)
                ['account_id' => $salesReturnId, 'debit' => $refundAmount, 'credit' => 0],
                // Credit Cash (Asset -) OR Credit Customer Wallet (Liability +)
                ['account_id' => $cashId, 'debit' => 0, 'credit' => $refundAmount],

                // B. INVENTORY LEG (Put value back onto shelves)
                // Debit Inventory Asset (Asset +)
                ['account_id' => $inventoryId, 'debit' => $costReversalAmount, 'credit' => 0],
                // Credit COGS (Expense -)
                ['account_id' => $cogsId, 'debit' => 0, 'credit' => $costReversalAmount],
            ]
        );

        return response()->json(['message' => 'Return processed, stock updated, and ledger adjusted.']);
    });
}
}
