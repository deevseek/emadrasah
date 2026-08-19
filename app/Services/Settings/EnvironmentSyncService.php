<?php

declare(strict_types=1);

namespace App\Services\Settings;

use RuntimeException;

class EnvironmentSyncService
{
    public function __construct(private ?string $path = null) {}

    public function path(): string
    {
        return $this->path ?? (string) config('bri.env_file', base_path('.env'));
    }

    public function writable(): bool
    {
        $path = $this->path();
        return is_file($path) ? is_writable($path) : is_writable(dirname($path));
    }

    /** @param array<string, mixed> $values */
    public function sync(array $values): string
    {
        $path = $this->path();
        if (! $this->writable()) {
            throw new RuntimeException('Pengaturan berhasil divalidasi tetapi file .env tidak dapat ditulis oleh user PHP-FPM.');
        }

        $original = is_file($path) ? (string) file_get_contents($path) : '';
        $this->backup($original);
        $content = $original;
        foreach ($values as $key => $value) {
            $line = $key.'='.$this->encode($value);
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';
            if (preg_match($pattern, $content) === 1) {
                $content = (string) preg_replace($pattern, $line, $content, 1);
            } else {
                $content .= ($content === '' || str_ends_with($content, "\n") ? '' : "\n").$line."\n";
            }
        }
        $tmp = $path.'.tmp.'.bin2hex(random_bytes(6));
        $handle = fopen($tmp, 'xb');
        if ($handle === false) throw new RuntimeException('File sementara .env tidak dapat dibuat.');
        try {
            if (fwrite($handle, $content) === false || ! fflush($handle)) throw new RuntimeException('File .env tidak dapat ditulis.');
            if (function_exists('fsync')) fsync($handle);
        } finally {
            fclose($handle);
        }
        @chmod($tmp, is_file($path) ? (fileperms($path) & 0777) : 0600);
        if (! rename($tmp, $path)) { @unlink($tmp); throw new RuntimeException('Penggantian file .env secara atomik gagal.'); }
        return $original;
    }

    public function restore(string $content): void
    {
        $tmp = $this->path().'.restore.'.bin2hex(random_bytes(4));
        file_put_contents($tmp, $content, LOCK_EX);
        rename($tmp, $this->path());
    }

    private function encode(mixed $value): string
    {
        if ($value === null) return '';
        if (is_bool($value)) return $value ? 'true' : 'false';
        $value = str_replace(["\\", "\r", "\n", '"'], ['\\\\', '\\r', '\\n', '\\"'], (string) $value);
        return preg_match('/[\s#="\']/', $value) ? '"'.$value.'"' : $value;
    }

    private function backup(string $content): void
    {
        $directory = storage_path('app/backups/env');
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) throw new RuntimeException('Direktori backup .env tidak dapat dibuat.');
        $file = $directory.'/.env-'.now()->format('Ymd-His-u');
        if (file_put_contents($file, $content, LOCK_EX) === false) throw new RuntimeException('Backup .env tidak dapat dibuat.');
        chmod($file, 0600);
        $backups = glob($directory.'/.env-*') ?: [];
        rsort($backups);
        foreach (array_slice($backups, 10) as $old) @unlink($old);
    }
}
