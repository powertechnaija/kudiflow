<?php

namespace App\Http\Controllers;

use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SetupController extends Controller
{
    public function openingBalance(Request $request, AccountingService $accounting)
    {
        $request->validate([
            'date' => 'required|date',
            'cash_on_hand' => 'required|numeric|min:0',
            'inventory_value' => 'required|numeric|min:0',
        ]);

        $storeId = Auth::user()->store_id;

        $accounting->createOpeningBalanceEntry(
            $storeId,
            $request->date,
            $request->cash_on_hand,
            $request->inventory_value
        );

        return response()->json(['message' => 'Opening balance recorded successfully']);
    }
}
