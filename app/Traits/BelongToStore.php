<?php

namespace App\Traits;

trait BelongToStore
{
    //
    protected static function bootBelongsToStore()
    {
        // 1. Reading Data: Automatically filter by the logged-in user's store
        if (Auth::check() && Auth::user()->store_id) {
            static::addGlobalScope('store', function (Builder $builder) {
                $builder->where('store_id', Auth::user()->store_id);
            });
        }

        // 2. Writing Data: Automatically set the store_id when creating
        static::creating(function ($model) {
            if (Auth::check() && Auth::user()->store_id) {
                $model->store_id = Auth::user()->store_id;
            }
        });
    }

    public function store()
    {
        return $this->belongsTo(\App\Models\Store::class);
    }

}
