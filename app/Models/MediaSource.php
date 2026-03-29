<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaSource extends Model
{
    protected $connection = 'tenant';

    protected $table = 'media_source';

    protected $fillable = ['media_id', 'source_type', 'source_id', 'usage'];

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
