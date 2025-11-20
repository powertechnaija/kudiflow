<?php

namespace App\Models;

use App\Traits\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory, BelongsToStore;

    protected $fillable = ['name', 'description', 'store_id'];

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
}
