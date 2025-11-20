<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'store_id',
        'sku',
        'price',
        'cost_price',
        'stock_quantity',
        'size',
        'color',
        'barcode'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
