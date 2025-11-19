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
            
            // Get Account IDs based on Codes
            $cashId = ChartOfAccount::where('code', AccountCodes::CASH)->first()->id;
            $inventoryId = ChartOfAccount::where('code', AccountCodes::INVENTORY_ASSET)->first()->id;
            $equityId = ChartOfAccount::where('code', AccountCodes::OWNERS_EQUITY)->first()->id;

            // Record Entry
            $accounting->recordEntry(
                $request->date,
                'Opening Balance Setup',
                'OP-'.time(),
                [
                    // Debit Assets (Increase)
                    ['account_id' => $cashId, 'debit' => $request->cash_on_hand, 'credit' => 0],
                    ['account_id' => $inventoryId, 'debit' => $request->inventory_value, 'credit' => 0],
                    
                    // Credit Equity (Increase ownership value)
                    ['account_id' => $equityId, 'debit' => 0, 'credit' => $totalEquity],
                ]
            );

            return response()->json(['message' => 'Opening balances established']);
        });
    }
}
