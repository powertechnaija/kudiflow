<?php

namespace App\Services;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Constants\AccountCodes;
use App\Models\ChartOfAccount;

class AccountingService
{
    public function createInitialChartOfAccounts($storeId)
    {
        $accounts = [
            // Assets
            ['name' => 'Cash', 'code' => AccountCodes::CASH, 'type' => 'asset'],
            ['name' => 'Accounts Receivable', 'code' => AccountCodes::ACCOUNTS_RECEIVABLE, 'type' => 'asset'],
            ['name' => 'Inventory', 'code' => AccountCodes::INVENTORY_ASSET, 'type' => 'asset'],
            ['name' => 'Prepaid Expenses', 'code' => AccountCodes::PREPAID_EXPENSES, 'type' => 'asset'],
            ['name' => 'Fixed Assets', 'code' => AccountCodes::FIXED_ASSETS, 'type' => 'asset'],
            ['name' => 'Accumulated Depreciation', 'code' => AccountCodes::ACCUMULATED_DEPRECIATION, 'type' => 'asset'],

            // Liabilities
            ['name' => 'Accounts Payable', 'code' => AccountCodes::ACCOUNTS_PAYABLE, 'type' => 'liability'],
            ['name' => 'Sales Tax Payable', 'code' => AccountCodes::SALES_TAX_PAYABLE, 'type' => 'liability'],
            ['name' => 'Wages Payable', 'code' => AccountCodes::WAGES_PAYABLE, 'type' => 'liability'],
            ['name' => 'Short-Term Loans', 'code' => AccountCodes::SHORT_TERM_LOANS, 'type' => 'liability'],
            ['name' => 'Long-Term Loans', 'code' => AccountCodes::LONG_TERM_LOANS, 'type' => 'liability'],

            // Equity
            ['name' => 'Owner\'s Equity', 'code' => AccountCodes::OWNERS_EQUITY, 'type' => 'equity'],
            ['name' => 'Retained Earnings', 'code' => AccountCodes::RETAINED_EARNINGS, 'type' => 'equity'],
            ['name' => 'Owner\'s Draw', 'code' => AccountCodes::OWNERS_DRAW, 'type' => 'equity'],

            // Revenue
            ['name' => 'Sales Revenue', 'code' => AccountCodes::SALES_REVENUE, 'type' => 'revenue'],
            ['name' => 'Sales Returns and Allowances', 'code' => AccountCodes::SALES_RETURNS_AND_ALLOWANCES, 'type' => 'revenue'],
            ['name' => 'Service Revenue', 'code' => AccountCodes::SERVICE_REVENUE, 'type' => 'revenue'],
            ['name' => 'Interest Income', 'code' => AccountCodes::INTEREST_INCOME, 'type' => 'revenue'],

            // Expenses
            ['name' => 'Cost of Goods Sold', 'code' => AccountCodes::COGS, 'type' => 'expense'],
            ['name' => 'Advertising Expense', 'code' => AccountCodes::ADVERTISING_EXPENSE, 'type' => 'expense'],
            ['name' => 'Bank Service Charges', 'code' => AccountCodes::BANK_SERVICE_CHARGES, 'type' => 'expense'],
            ['name' => 'Depreciation Expense', 'code' => AccountCodes::DEPRECIATION_EXPENSE, 'type' => 'expense'],
            ['name' => 'Insurance Expense', 'code' => AccountCodes::INSURANCE_EXPENSE, 'type' => 'expense'],
            ['name' => 'Rent Expense', 'code' => AccountCodes::RENT_EXPENSE, 'type' => 'expense'],
            ['name' => 'Salaries and Wages Expense', 'code' => AccountCodes::SALARIES_AND_WAGES_EXPENSE, 'type' => 'expense'],
            ['name' => 'Utilities Expense', 'code' => AccountCodes::UTILITIES_EXPENSE, 'type' => 'expense'],
            ['name' => 'Supplies Expense', 'code' => AccountCodes::SUPPLIES_EXPENSE, 'type' => 'expense'],
        ];

        foreach ($accounts as $account) {
            ChartOfAccount::firstOrCreate(
                [
                    'store_id' => $storeId,
                    'code' => $account['code'],
                ],
                [
                    'name' => $account['name'],
                    'type' => $account['type'],
                ]
            );
        }
    }

    public function getAccountId($storeId, $code)
    {
        return \App\Models\ChartOfAccount::where('store_id', $storeId)
            ->where('code', $code)
            ->firstOrFail()
            ->id;
    }

    public function createOpeningBalanceEntry($storeId, $date, $cashOnHand, $inventoryValue)
    {
        $this->createInitialChartOfAccounts($storeId);
        
        $totalEquity = $cashOnHand + $inventoryValue;

        return $this->recordEntry(
            $storeId,
            $date,
            'Opening Balance',
            null,
            [
                [
                    'chart_of_account_id' => $this->getAccountId($storeId, AccountCodes::CASH),
                    'debit' => $cashOnHand,
                    'credit' => 0,
                ],
                [
                    'chart_of_account_id' => $this->getAccountId($storeId, AccountCodes::INVENTORY_ASSET),
                    'debit' => $inventoryValue,
                    'credit' => 0,
                ],
                [
                    'chart_of_account_id' => $this->getAccountId($storeId, AccountCodes::OWNERS_EQUITY),
                    'debit' => 0,
                    'credit' => $totalEquity,
                ],
            ]
        );
    }

    public function recordEntry($storeId, $date, $description, $ref, array $items)
    {
        // Validate Double Entry (Debits must equal Credits)
        $totalDebit = collect($items)->sum('debit');
        $totalCredit = collect($items)->sum('credit');

        if (bcsub($totalDebit, $totalCredit, 2) != 0.00) {
            throw new Exception("Journal Entry Unbalanced: Debit $totalDebit != Credit $totalCredit");
        }

        $entry = JournalEntry::create([
            'store_id' => $storeId,
            'date' => $date,
            'description' => $description,
            'reference_number' => $ref
        ]);

        foreach ($items as $item) {
            JournalItem::create([
                'journal_entry_id' => $entry->id,
                'chart_of_account_id' => $item['chart_of_account_id'],
                'debit' => $item['debit'],
                'credit' => $item['credit'],
                'store_id' => $storeId
            ]);
        }
        return $entry;
    }
}
