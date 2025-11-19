namespace App\Services;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use Illuminate\Support\Facades\DB;
use Exception;

class AccountingService
{
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
                    'chart_of_account_id' => $item['account_id'],
                    'debit' => $item['debit'],
                    'credit' => $item['credit']
                ]);
            }
            return $entry;
        });
    }
}