<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToStore;

class ChartOfAccount extends Model
{
    use HasFactory, BelongsToStore;

    protected $fillable = [
        'name',
        'type',
        'code',
        'store_id'
    ];
}
