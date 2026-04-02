<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    /** Website content lives on the active tenant DB (see TenantMiddleware). */
    protected $connection = 'tenant';

    protected $fillable = [
        'locale',
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
