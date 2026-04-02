<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeCard extends Model
{
    protected $connection = 'tenant';

    protected $fillable = ['locale', 'title', 'description', 'icon', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<HomeCard>  $query
     * @return \Illuminate\Database\Eloquent\Builder<HomeCard>
     */
    public function scopeOrdered(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /** Icon keys and labels for "Why use our PDF compressor" cards. Frontend maps keys to emoji/SVG. */
    public static function iconOptions(): array
    {
        return [
            'lightning' => 'Fast compression',
            'quality' => 'Quality control',
            'lock' => 'Privacy first',
            'star' => 'Free to use',
            'document' => 'Document',
            'shield' => 'Security / Shield',
            'heart' => 'Favourite / Heart',
            'cloud' => 'Cloud',
            'download' => 'Download',
            'upload' => 'Upload',
            'check' => 'Check / Done',
            'image' => 'Image',
            'file-plus' => 'Add file',
            'layers' => 'Layers',
            'sparkle' => 'Sparkle',
            'zap' => 'Zap / Speed',
            'settings' => 'Settings',
            'globe' => 'Globe / Worldwide',
            'mobile' => 'Mobile friendly',
            'clock' => 'Fast / Time',
        ];
    }
}
