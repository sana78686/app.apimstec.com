<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    /** Lives on the master DB, never on the tenant DB. */
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
}
