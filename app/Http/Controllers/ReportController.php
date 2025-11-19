<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReportController extends Controller
{
    
    public function profitAndLoss(Request $request)
    {
        // Formula: Revenue - Expenses
        $revenue = JournalItem::whereHas('account', fn($q) => $q->where('type', 'revenue'))
            ->sum('credit'); // Revenues are Credit normal
            
        $expenses = JournalItem::whereHas('account', fn($q) => $q->where('type', 'expense'))
            ->sum('debit'); // Expenses are Debit normal

        return response()->json([
            'revenue' => $revenue,
            'expenses' => $expenses,
            'net_profit' => $revenue - $expenses
        ]);
    }

    public function balanceSheet(Request $request)
    {
        // Formula: Assets = Liabilities + Equity
        $assets = JournalItem::whereHas('account', fn($q) => $q->where('type', 'asset'))
            ->sum(DB::raw('debit - credit'));

        $liabilities = JournalItem::whereHas('account', fn($q) => $q->where('type', 'liability'))
            ->sum(DB::raw('credit - debit'));
            
        $equity = JournalItem::whereHas('account', fn($q) => $q->where('type', 'equity'))
            ->sum(DB::raw('credit - debit'));

        return response()->json([
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
        ]);
    }
}
