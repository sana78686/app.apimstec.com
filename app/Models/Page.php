<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    protected $fillable = [
        'parent_id',
        'title',
        'slug',
        'content',
        'meta_title',
        'meta_description',
        'focus_keyword',
        'canonical_url',
        'meta_robots',
        'og_title',
        'og_description',
        'og_image',
        'schema_type',
        'schema_data',
        'placement',
        'is_published',
        'visibility',
        'sort_order',
    ];

    public const VISIBILITY_DRAFT = 'draft';
    public const VISIBILITY_PRIVATE = 'private';
    public const VISIBILITY_PUBLISHED = 'published';

    public static function visibilityOptions(): array
    {
        return [
            self::VISIBILITY_DRAFT => 'Draft (noindex)',
            self::VISIBILITY_PRIVATE => 'Private (hidden)',
            self::VISIBILITY_PUBLISHED => 'Published (index allowed)',
        ];
    }

    /** Meta robots value for current visibility. */
    public function metaRobotsForVisibility(): string
    {
        return match ($this->visibility ?? self::VISIBILITY_PUBLISHED) {
            self::VISIBILITY_DRAFT => 'noindex,follow',
            self::VISIBILITY_PRIVATE => 'noindex,nofollow',
            default => 'index,follow',
        };
    }

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort_order' => 'integer',
            'schema_data' => 'array',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Page::class, 'parent_id')->orderBy('sort_order');
    }

    /** Whether this page is a top-level (parent) page. */
    public function isParent(): bool
    {
        return $this->parent_id === null;
    }
}
