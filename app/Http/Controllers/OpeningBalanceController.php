<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AccountingService;
use App\Models\ChartOfAccount;
use App\Constants\AccountCodes;
use Illuminate\Support\Facades\DB;


class OpeningBalanceController extends Controller
{
    public function store(Request $request, AccountingService $accounting)
    {
        $request->validate([
            'date' => 'required|date',
            'cash_on_hand' => 'numeric|min:0',
            'inventory_value' => 'numeric|min:0',
        ]);

        return DB::transaction(function() use ($request, $accounting) {
            $totalEquity = $request->cash_on_hand + $request->inventory_value;

            // SAAS REFACTOR: Use Helper to get Store-Specific IDs
            $cashId = $accounting->getAccountId(AccountCodes::CASH);
            $inventoryId = $accounting->getAccountId(AccountCodes::INVENTORY_ASSET);
            $equityId = $accounting->getAccountId(AccountCodes::OWNERS_EQUITY);

            $accounting->recordEntry(
                $request->date,
                'Opening Balance Setup',
                'OP-START',
                [
                    ['account_id' => $cashId, 'debit' => $request->cash_on_hand, 'credit' => 0],
                    ['account_id' => $inventoryId, 'debit' => $request->inventory_value, 'credit' => 0],
                    ['account_id' => $equityId, 'debit' => 0, 'credit' => $totalEquity],
                ]
            );

            return response()->json(['message' => 'Opening balances set']);
        });
    }
}
