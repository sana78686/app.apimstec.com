<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrokenLinkLog extends Model
{
    protected $table = 'broken_link_logs';

    protected $fillable = [
        'path',
        'hit_count',
        'referer',
        'first_seen_at',
        'last_seen_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /** Paths we do not log (admin, auth, api, etc.). */
    public static function shouldSkipPath(string $path): bool
    {
        $skip = [
            'api', 'seo', 'dashboard', 'login', 'register', 'logout',
            'password', 'email', 'profile', 'users', 'roles', 'pages',
            'blogs', 'content-manager', 'media', 'sanctum', '_',
        ];
        $first = explode('/', $path)[0] ?? $path;
        return $first === '' || in_array($first, $skip, true);
    }

    /** Record a 404 hit for the given path. */
    public static function log404(string $path, ?string $referer = null): void
    {
        $path = trim($path, '/');
        if ($path === '' || self::shouldSkipPath($path)) {
            return;
        }

        $now = now();
        $log = self::where('path', $path)->first();
        if ($log) {
            $log->increment('hit_count');
            $log->update(['last_seen_at' => $now, 'referer' => $referer ?? $log->referer]);
        } else {
            self::create([
                'path' => $path,
                'hit_count' => 1,
                'referer' => $referer,
                'first_seen_at' => $now,
                'last_seen_at' => $now,
            ]);
        }
    }

    /** Mark as resolved (redirect was created). */
    public function markResolved(): void
    {
        $this->update(['resolved_at' => now()]);
    }

    /** Scope: only unresolved (no redirect created yet). */
    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }
}
