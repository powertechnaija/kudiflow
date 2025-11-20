<?php

namespace App\Http\Controllers;

use App\Models\JournalItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Constants\AccountCodes;

class ReportController extends Controller
{
    /**
     * Profit & Loss (Income Statement)
     * Formula: Net Profit = Revenue - COGS - Expenses
     */
    public function profitAndLoss(Request $request)
    {
        $storeId = auth()->user()->store_id;

        // 1. Calculate Revenue
        $revenue = JournalItem::where('store_id', $storeId)->whereHas('chartOfAccount', function($q) {
            $q->where('type', 'revenue');
        })->sum('credit');
        $salesReturns = JournalItem::where('store_id', $storeId)->whereHas('chartOfAccount', function($q) {
             $q->where('type', 'revenue');
        })->sum('debit');
        $netRevenue = $revenue - $salesReturns;

        // 2. Calculate Cost of Goods Sold (COGS)
        $cogs = JournalItem::where('store_id', $storeId)->whereHas('chartOfAccount', function($q) {
            $q->where('code', AccountCodes::COGS);
        })->sum(DB::raw('debit - credit'));

        $grossProfit = $netRevenue - $cogs;

        // 3. Calculate Expenses
        $expenses = JournalItem::where('store_id', $storeId)->whereHas('chartOfAccount', function($q) {
            $q->where('type', 'expense')->where('code', '!=', AccountCodes::COGS); // Exclude COGS
        })->sum(DB::raw('debit - credit'));

        return response()->json([
            'breakdown' => [
                'gross_revenue' => $revenue,
                'returns_discounts' => $salesReturns,
                'net_revenue' => $netRevenue,
                'cost_of_goods_sold' => $cogs,
                'gross_profit' => $grossProfit,
                'operating_expenses' => $expenses,
            ],
            'net_profit' => $grossProfit - $expenses
        ]);
    }

    /**
     * Balance Sheet
     * Formula: Assets = Liabilities + Equity
     */
    public function balanceSheet(Request $request)
    {
        $storeId = auth()->user()->store_id;

        // Assets (Debit Normal)
        $assets = JournalItem::where('store_id', $storeId)->whereHas('chartOfAccount', function($q) {
            $q->where('type', 'asset');
        })->sum(DB::raw('debit - credit'));

        // Liabilities (Credit Normal)
        $liabilities = JournalItem::where('store_id', $storeId)->whereHas('chartOfAccount', function($q) {
            $q->where('type', 'liability');
        })->sum(DB::raw('credit - debit'));

        // Equity (Credit Normal)
        $equity = JournalItem::where('store_id', $storeId)->whereHas('chartOfAccount', function($q) {
            $q->where('type', 'equity');
        })->sum(DB::raw('credit - debit'));

        // Calculate Net Income (Retained Earnings) for the current period
        $revenue_credit = JournalItem::where('store_id', $storeId)->whereHas('chartOfAccount', fn($q) => $q->where('type', 'revenue'))->sum('credit');
        $revenue_debit = JournalItem::where('store_id', $storeId)->whereHas('chartOfAccount', fn($q) => $q->where('type', 'revenue'))->sum('debit');
        $cogs = JournalItem::where('store_id', $storeId)->whereHas('chartOfAccount', fn($q) => $q->where('code', AccountCodes::COGS))->sum(DB::raw('debit - credit'));
        $expense_debit = JournalItem::where('store_id', $storeId)->whereHas('chartOfAccount', fn($q) => $q->where('type', 'expense')->where('code', '!=', AccountCodes::COGS))->sum('debit');
        $expense_credit = JournalItem::where('store_id', $storeId)->whereHas('chartOfAccount', fn($q) => $q->where('type', 'expense')->where('code', '!=', AccountCodes::COGS))->sum('credit');

        $net_income = ($revenue_credit - $revenue_debit) - $cogs - ($expense_debit - $expense_credit);

        $total_equity_and_liabilities = $liabilities + $equity + $net_income;

        return response()->json([
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'current_net_income' => $net_income,
            'check' => [
                'assets' => $assets,
                'liabilities_plus_equity' => $total_equity_and_liabilities,
                'is_balanced' => abs($assets - $total_equity_and_liabilities) < 0.01
            ]
        ]);
    }

    /**
     * Cash Flow (Simplified)
     * Tracks movement specifically in the Cash Account
     */
    public function cashFlow(Request $request, \App\Services\AccountingService $accounting)
    {
         $cashAccountId = $accounting->getAccountId(auth()->user()->store_id, \App\Constants\AccountCodes::CASH);

         $inflow = JournalItem::where('chart_of_account_id', $cashAccountId)->sum('debit');
         $outflow = JournalItem::where('chart_of_account_id', $cashAccountId)->sum('credit');

         return response()->json([
             'cash_in' => $inflow,
             'cash_out' => $outflow,
             'net_cash_flow' => $inflow - $outflow
         ]);
    }
}
