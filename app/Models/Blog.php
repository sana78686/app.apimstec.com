<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Blog extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'published_at',
        'user_id',
        'is_published',
        'visibility',
        'schema_type',
        'schema_data',
        'meta_title',
        'meta_description',
        'canonical_url',
        'meta_robots',
        'og_title',
        'og_description',
        'og_image',
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
            'published_at' => 'datetime',
            'is_published' => 'boolean',
            'schema_data' => 'array',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
