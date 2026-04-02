<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaqItem extends Model
{
    protected $connection = 'tenant';

    protected $fillable = ['locale', 'question', 'answer', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<FaqItem>  $query
     * @return \Illuminate\Database\Eloquent\Builder<FaqItem>
     */
    public function scopeOrdered(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
