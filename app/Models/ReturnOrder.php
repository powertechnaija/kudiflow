<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnOrder extends Model
{
    use HasFactory;

    protected $fillable = ['store_id', 'order_id', 'reason', 'status'];

    public function items()
    {
        return $this->hasMany(ReturnOrderItem::class);
    }
}
