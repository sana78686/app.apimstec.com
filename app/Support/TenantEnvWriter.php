<?php

namespace App\Support;

use App\Models\Domain;
use RuntimeException;

/**
 * Writes the currently selected site's DB credentials into .env as CMS_TENANT_* keys.
 *
 * Master CMS data (users, domains, roles) must keep using DB_* — never overwrite those.
 */
class TenantEnvWriter
{
    /** @var list<string> */
    private const KEYS = [
        'CMS_TENANT_HOST',
        'CMS_TENANT_PORT',
        'CMS_TENANT_DATABASE',
        'CMS_TENANT_USERNAME',
        'CMS_TENANT_PASSWORD',
    ];

    public function __construct(
        private readonly string $envPath,
    ) {}

    public static function forApplication(): self
    {
        return new self(base_path('.env'));
    }

    public function writeFromDomain(Domain $domain): void
    {
        $this->mergeValues([
            'CMS_TENANT_HOST' => (string) $domain->db_host,
            'CMS_TENANT_PORT' => (string) $domain->db_port,
            'CMS_TENANT_DATABASE' => (string) $domain->db_name,
            'CMS_TENANT_USERNAME' => (string) $domain->db_username,
            'CMS_TENANT_PASSWORD' => $domain->decryptedPassword(),
        ]);
    }

    /**
     * Remove CMS_TENANT_* lines so config falls back to DB_* for the tenant connection default.
     */
    public function removeTenantKeys(): void
    {
        if (! is_file($this->envPath)) {
            return;
        }

        $content = file_get_contents($this->envPath);
        if ($content === false) {
            throw new RuntimeException('Could not read .env.');
        }

        foreach (self::KEYS as $key) {
            $content = $this->removeKeyLine($content, $key);
        }

        $content = preg_replace('/^\R?# CMS: active site DB \(auto-updated on domain switch\)\R?/m', '', $content) ?? $content;

        $this->atomicWrite($content);
    }

    /**
     * @param  array<string, string>  $values
     */
    private function mergeValues(array $values): void
    {
        if (! is_file($this->envPath)) {
            throw new RuntimeException('.env not found at '.$this->envPath);
        }

        $content = file_get_contents($this->envPath);
        if ($content === false) {
            throw new RuntimeException('Could not read .env.');
        }

        $toAppend = [];
        foreach ($values as $key => $value) {
            $line = $key.'='.$this->escapeValue($value);
            $pattern = '/^'.preg_quote($key, '/').'=(.*)$/m';
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $line, $content);
            } else {
                $toAppend[] = $line;
            }
        }

        if ($toAppend !== []) {
            $trimmed = rtrim($content);
            $prefix = str_ends_with($trimmed, "\n") || $trimmed === '' ? '' : "\n";
            $content = $trimmed.$prefix."\n\n# CMS: active site DB (auto-updated on domain switch)\n"
                .implode("\n", $toAppend)."\n";
        }

        $this->atomicWrite($content);
    }

    private function removeKeyLine(string $content, string $key): string
    {
        $pattern = '/^'.preg_quote($key, '/').'=(.*)\R?/m';

        return preg_replace($pattern, '', $content) ?? $content;
    }

    private function escapeValue(string $value): string
    {
        $needsQuotes = $value === ''
            || preg_match('/[\s#"\']/', $value)
            || str_contains($value, '$');

        if (! $needsQuotes) {
            return $value;
        }

        return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
    }

    private function atomicWrite(string $content): void
    {
        $dir = dirname($this->envPath);
        if (! is_dir($dir) || ! is_writable($dir)) {
            throw new RuntimeException('.env directory is not writable.');
        }

        $temp = $this->envPath.'.'.bin2hex(random_bytes(4)).'.tmp';
        if (file_put_contents($temp, $content, LOCK_EX) === false) {
            throw new RuntimeException('Could not write temporary .env file.');
        }

        if (! rename($temp, $this->envPath)) {
            @unlink($temp);
            throw new RuntimeException('Could not replace .env file.');
        }
    }
}
