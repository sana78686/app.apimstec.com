<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentSectionItem extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'content_section_id',
        'title',
        'body',
        'item_type',
        'media_type',
        'media_value',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<ContentSectionItem>  $query
     * @return \Illuminate\Database\Eloquent\Builder<ContentSectionItem>
     */
    public function scopeOrdered(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(ContentSection::class, 'content_section_id');
    }
}

