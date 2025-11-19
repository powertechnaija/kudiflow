<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    protected $fillable = [
        'date',
        'description',
        'reference_number'
    ];

    public function items()
    {
        return $this->hasMany(JournalItem::class);
    }
}
