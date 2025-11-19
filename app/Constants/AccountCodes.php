<?php

namespace App\Constants;

class AccountCodes {
    // Assets
    const CASH = '1001';
    const INVENTORY_ASSET = '1200'; // The value of stock sitting on shelves
    const ACCOUNTS_RECEIVABLE = '1100';

    // Liabilities
    const ACCOUNTS_PAYABLE = '2000';
    const CUSTOMER_WALLET = '2100'; // Money owed to customers (returns)

    // Equity
    const OWNERS_EQUITY = '3000';

    // Revenue
    const SALES_REVENUE = '4000';
    const SALES_RETURNS = '4100'; // Contra-Revenue account

    // Expenses
    const COGS = '5000'; // Cost of Goods Sold
}