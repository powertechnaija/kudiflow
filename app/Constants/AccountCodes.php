<?php

namespace App\Constants;

class AccountCodes {
    // Assets
    const CASH = '1001';
    const ACCOUNTS_RECEIVABLE = '1100';
    const INVENTORY_ASSET = '1200';
    const PREPAID_EXPENSES = '1300';
    const FIXED_ASSETS = '1500';
    const ACCUMULATED_DEPRECIATION = '1501';

    // Liabilities
    const ACCOUNTS_PAYABLE = '2000';
    const CUSTOMER_WALLET = '2100';
    const SALES_TAX_PAYABLE = '2110';
    const WAGES_PAYABLE = '2200';
    const SHORT_TERM_LOANS = '2300';
    const LONG_TERM_LOANS = '2500';

    // Equity
    const OWNERS_EQUITY = '3000';
    const RETAINED_EARNINGS = '3200';
    const OWNERS_DRAW = '3300';

    // Revenue
    const SALES_REVENUE = '4000';
    const SALES_RETURNS_AND_ALLOWANCES = '4100';
    const SERVICE_REVENUE = '4200';
    const INTEREST_INCOME = '4500';

    // Expenses
    const COGS = '5000';
    const ADVERTISING_EXPENSE = '5100';
    const BANK_SERVICE_CHARGES = '5200';
    const DEPRECIATION_EXPENSE = '5300';
    const INSURANCE_EXPENSE = '5400';
    const RENT_EXPENSE = '5500';
    const SALARIES_AND_WAGES_EXPENSE = '5600';
    const UTILITIES_EXPENSE = '5700';
    const SUPPLIES_EXPENSE = '5800';
}
