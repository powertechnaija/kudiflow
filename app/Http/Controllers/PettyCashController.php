<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\AccountingService;
use App\Constants\AccountCodes;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;

class PettyCashController extends Controller
{
    public function store(Request $request, AccountingService $accounting)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'expense_account_id' => 'required|exists:chart_of_accounts,id' 
            // Note: 'exists' automatically checks store_id via BelongsToStore trait if applied,
            // otherwise we must validate ownership manually. 
            // Assuming Trait is applied to ChartOfAccount model.
        ]);

        // Verify the selected account is actually an Expense account
        $expenseAccount = ChartOfAccount::find($request->expense_account_id);
        if ($expenseAccount->type !== 'expense') {
            return response()->json(['message' => 'Selected account is not an expense account.'], 422);
        }

        // Resolve Cash Account ID
        $cashId = $accounting->getAccountId(AccountCodes::CASH);

        $accounting->recordEntry(
            now(),
            "Petty Cash: " . $request->description,
            "PC-" . time(),
            [
                // Debit Expense (Increase Expense)
                ['account_id' => $expenseAccount->id, 'debit' => $request->amount, 'credit' => 0],
                // Credit Cash (Decrease Asset)
                ['account_id' => $cashId, 'debit' => 0, 'credit' => $request->amount],
            ]
        );

        return response()->json(['message' => 'Petty cash expense recorded successfully']);
    }
}