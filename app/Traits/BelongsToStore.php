<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Trait BelongsToStore
 * Automatically scopes queries to the current user\'s store.
 */
trait BelongsToStore
{
    protected static function bootBelongsToStore()
    {
        // On model creation, automatically set the store_id from the authenticated user.
        static::creating(function ($model) {
            if (Auth::check() && !$model->store_id) {
                $model->store_id = Auth::user()->store_id;
            }
        });

        // Automatically add a global scope to all queries on this model.
        static::addGlobalScope('store', function (Builder $builder) {
            if (Auth::check()) {
                $builder->where((new self)->getTable() . '.store_id', Auth::user()->store_id);
            }
        });
    }
}
