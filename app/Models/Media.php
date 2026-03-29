<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Media extends Model
{
    protected $fillable = [
        'path',
        'filename',
        'alt_text',
        'file_size',
        'mime_type',
        'webp_path',
    ];

    public function sources(): HasMany
    {
        return $this->hasMany(MediaSource::class);
    }

    /** Whether this media path is a local file (can be compressed/converted). */
    public function isLocal(): bool
    {
        $path = $this->path ?? '';
        return ! preg_match('#^https?://#i', $path);
    }

    /** Absolute filesystem path for local media. */
    public function absolutePath(): ?string
    {
        if (! $this->isLocal()) {
            return null;
        }
        $path = public_path(ltrim($this->path, '/'));
        return file_exists($path) ? $path : null;
    }
}
