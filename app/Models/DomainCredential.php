<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainCredential extends Model
{
    protected $fillable = [
        'domain',
        'email_credentials',
        'plesk_credentials',
        'website_credentials',
        'portal_credentials',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'email_credentials' => 'array',
            'plesk_credentials' => 'array',
            'website_credentials' => 'array',
            'portal_credentials' => 'array',
        ];
    }
}
