<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ProductVariant;
use App\Services\AccountingService;
use App\Constants\AccountCodes;

class PurchaseController extends Controller
{
    protected $accounting;

    public function __construct(AccountingService $accounting)
    {
        $this->accounting = $accounting;
    }

    /**
     * Record a new inventory purchase.
     */
    public function store(Request $request)
    {
        $request->validate([
            'supplier_name' => 'required|string',
            'payment_method' => 'required|in:cash,credit',
            'items' => 'required|array',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        $storeId = auth()->user()->store_id;

        // Use a database transaction
        $dbConnection = DB::connection()->getPdo()->inTransaction();
        if (!$dbConnection) {
            DB::beginTransaction();
        }

        try {
            $totalCost = 0;

            // 1. Update Inventory Stock Levels
            foreach ($request->items as $item) {
                $variant = ProductVariant::find($item['variant_id']);
                $variant->increment('stock_quantity', $item['quantity']);
                $totalCost += $item['quantity'] * $item['unit_cost'];
            }

            // 2. Prepare Journal Entry Items
            $entryItems = [
                // Debit Inventory Asset Account
                [
                    'chart_of_account_id' => $this->accounting->getAccountId($storeId, AccountCodes::INVENTORY_ASSET),
                    'debit' => $totalCost,
                    'credit' => 0
                ],
            ];

            if ($request->payment_method === 'credit') {
                // Credit Accounts Payable
                $entryItems[] = [
                    'chart_of_account_id' => $this->accounting->getAccountId($storeId, AccountCodes::ACCOUNTS_PAYABLE),
                    'debit' => 0,
                    'credit' => $totalCost
                ];
            } else { // cash
                // Credit Cash Account
                $entryItems[] = [
                    'chart_of_account_id' => $this->accounting->getAccountId($storeId, AccountCodes::CASH),
                    'debit' => 0,
                    'credit' => $totalCost
                ];
            }

            // 3. Record the Journal Entry
            $this->accounting->recordEntry(
                $storeId,
                now(),
                'Inventory Purchase from ' . $request->supplier_name,
                null,
                $entryItems
            );

            if (!$dbConnection) {
                DB::commit();
            }
            return response()->json(['message' => 'Purchase recorded successfully'], 201);

        } catch (\Exception $e) {
            if (!$dbConnection) {
                DB::rollBack();
            }
            return response()->json(['message' => 'Error recording purchase: ' . $e->getMessage()], 500);
        }
    }
}
