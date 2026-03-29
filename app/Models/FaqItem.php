<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaqItem extends Model
{
    protected $fillable = ['question', 'answer', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public static function ordered(): \Illuminate\Database\Eloquent\Builder
    {
        return static::orderBy('sort_order')->orderBy('id');
    }
}
