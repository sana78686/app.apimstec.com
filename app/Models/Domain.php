<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    /** CMS registry DB only (`mysql` connection), never a site tenant DB. */
    protected $connection = 'mysql';

    protected $fillable = [
        'name',
        'domain',
        'frontend_url',
        'db_host',
        'db_port',
        'db_name',
        'db_username',
        'db_password',
        'is_active',
        'is_default',
    ];

    protected $hidden = ['db_password'];

    protected $casts = [
        'is_active'  => 'boolean',
        'is_default' => 'boolean',
        'db_port'    => 'integer',
    ];

    /** Return the decrypted password (used only inside TenantMiddleware). */
    public function decryptedPassword(): string
    {
        try {
            return decrypt($this->db_password);
        } catch (\Throwable) {
            return $this->db_password; // fallback if not yet encrypted (legacy)
        }
    }

    /** Build the runtime config array for this domain's DB connection. */
    public function connectionConfig(): array
    {
        return [
            'driver'    => 'mysql',
            'host'      => $this->db_host,
            'port'      => $this->db_port,
            'database'  => $this->db_name,
            'username'  => $this->db_username,
            'password'  => $this->decryptedPassword(),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'strict'    => true,
            'engine'    => null,
        ];
    }

    /**
     * True when this domain's credentials point at the same database as the CMS registry (mysql).
     * Schema actions must never run on the registry (domains + admin data live there).
     */
    public function targetsMasterDatabase(): bool
    {
        $master = config('database.connections.mysql', []);
        $mHost  = self::normalizeDbHost((string) ($master['host'] ?? ''));
        $mPort  = (int) ($master['port'] ?? 3306);
        $mDb    = (string) ($master['database'] ?? '');

        $dHost = self::normalizeDbHost((string) $this->db_host);
        $dPort = (int) $this->db_port;
        $dDb   = (string) $this->db_name;

        return $dHost === $mHost && $dPort === $mPort && $dDb === $mDb;
    }

    private static function normalizeDbHost(string $host): string
    {
        $h = strtolower(trim($host));
        if ($h === 'localhost') {
            $h = '127.0.0.1';
        }

        return $h;
    }

    /**
     * Public marketing site origin for sitemap / robots / SEO (not the CMS app URL).
     * Uses frontend_url when set; otherwise https://{domain} without leading www.
     */
    public function publicSiteBaseUrl(): string
    {
        $front = trim((string) ($this->frontend_url ?? ''));
        if ($front !== '') {
            $front = preg_replace('#/+$#u', '', $front) ?? $front;

            return rtrim($front, '/');
        }

        $host = strtolower(trim((string) ($this->domain ?? '')));
        $host = preg_replace('#^www\.#i', '', $host);

        return $host !== '' ? 'https://'.$host : '';
    }
}
