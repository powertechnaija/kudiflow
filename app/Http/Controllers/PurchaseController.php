<?php

namespace App\Http\Controllers;

use App\Constants\AccountCodes;
use App\Models\ProductVariant;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    //
    public function store(Request $request, AccountingService $accounting)
    {
        $request->validate([
            'supplier_name' => 'required|string',
            'items' => 'required|array',
            'payment_method' => 'required|in:cash,credit', // Cash vs Accounts Payable
        ]);

        return DB::transaction(function () use ($request, $accounting) {
            $totalCost = 0;

            // 1. Update Inventory
            foreach ($request->items as $item) {
                // Trait ensures we only update our own store's products
                $variant = ProductVariant::lockForUpdate()->findOrFail($item['variant_id']);
                
                // Update Weighted Average Cost could be done here, 
                // but simply updating current cost price for now:
                $variant->cost_price = $item['unit_cost'];
                $variant->increment('stock_quantity', $item['quantity']);
                
                $totalCost += ($item['quantity'] * $item['unit_cost']);
            }

            // 2. Resolve Accounts
            $inventoryId = $accounting->getAccountId(AccountCodes::INVENTORY_ASSET);
            
            $creditAccountId = match ($request->payment_method) {
                'cash' => $accounting->getAccountId(AccountCodes::CASH),
                'credit' => $accounting->getAccountId(AccountCodes::ACCOUNTS_PAYABLE),
            };

            // 3. Record Journal
            $accounting->recordEntry(
                now(),
                "Purchase from {$request->supplier_name}",
                'PUR-' . time(),
                [
                    // Debit Inventory (Asset Increases)
                    ['account_id' => $inventoryId, 'debit' => $totalCost, 'credit' => 0],
                    // Credit Cash or Payable (Asset Decreases or Liability Increases)
                    ['account_id' => $creditAccountId, 'debit' => 0, 'credit' => $totalCost],
                ]
            );

            return response()->json(['message' => 'Stock updated and transaction recorded']);
        });
    }
}
