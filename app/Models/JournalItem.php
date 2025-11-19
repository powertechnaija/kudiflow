<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalItem extends Model
{
    protected $fillable = [
        'journal_entry_id',
        'chart_of_account_id',
        'debit',
        'credit'
    ];

    public function entry()
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
