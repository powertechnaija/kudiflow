<?php

namespace App\Services;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use Illuminate\Support\Facades\DB;
use Exception;

class AccountingService
{
    private function getAccountId($code)
    {
        $storeId = auth()->user()->store_id;
        
        return \App\Models\ChartOfAccount::withoutGlobalScope('store') // Optional if trait applied
            ->where('store_id', $storeId)
            ->where('code', $code)
            ->firstOrFail()
            ->id;
    }
    public function recordEntry($date, $description, $ref, array $items)
    {
        return DB::transaction(function () use ($date, $description, $ref, $items) {
            // Validate Double Entry (Debits must equal Credits)
            $totalDebit = collect($items)->sum('debit');
            $totalCredit = collect($items)->sum('credit');

            if (bcsub($totalDebit, $totalCredit, 2) != 0.00) {
                throw new Exception("Journal Entry Unbalanced: Debit $totalDebit != Credit $totalCredit");
            }

            $entry = JournalEntry::create([
                'date' => $date,
                'description' => $description,
                'reference_number' => $ref
            ]);

            foreach ($items as $item) {
                JournalItem::create([
                    'journal_entry_id' => $entry->id,
                    'chart_of_account_id' => $item['chart_of_account_id'],
                    'debit' => $item['debit'],
                    'credit' => $item['credit']
                ]);
            }
            return $entry;
        });
    }
}