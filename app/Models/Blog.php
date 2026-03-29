<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Blog extends Model
{
    /** Website content lives on the active tenant DB (see TenantMiddleware). */
    protected $connection = 'tenant';

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

    public const VISIBILITY_DRAFT    = 'draft';
    public const VISIBILITY_VISIBLE  = 'visible';
    public const VISIBILITY_DISABLED = 'disabled';

    public static function visibilityOptions(): array
    {
        return [
            self::VISIBILITY_DRAFT    => 'Draft',
            self::VISIBILITY_VISIBLE  => 'Visible',
            self::VISIBILITY_DISABLED => 'Disabled',
        ];
    }

    /** Meta robots value for current visibility. */
    public function metaRobotsForVisibility(): string
    {
        return match ($this->visibility ?? self::VISIBILITY_DRAFT) {
            self::VISIBILITY_VISIBLE  => 'index,follow',
            self::VISIBILITY_DISABLED => 'noindex,nofollow',
            default                   => 'noindex,follow', // draft
        };
    }

    /** True only when status is 'visible'. */
    public function isVisible(): bool
    {
        return $this->visibility === self::VISIBILITY_VISIBLE;
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
