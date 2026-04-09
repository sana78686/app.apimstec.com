<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentSection extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'locale',
        'title',
        'description',
        'layout',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<ContentSection>  $query
     * @return \Illuminate\Database\Eloquent\Builder<ContentSection>
     */
    public function scopeOrdered(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ContentSectionItem::class, 'content_section_id')->orderBy('sort_order')->orderBy('id');
    }
}

