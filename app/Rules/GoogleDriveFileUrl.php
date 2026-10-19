<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class GoogleDriveFileUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $parts = is_string($value) ? parse_url($value) : false;
        if (! $parts || ($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) {
            $fail('Gunakan tautan HTTPS file Google Drive atau Google Docs yang valid.');

            return;
        }
        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';
        parse_str($parts['query'] ?? '', $query);
        $driveFile = $host === 'drive.google.com' && (
            preg_match('~^/file/d/[A-Za-z0-9_-]+(?:/|$)~', $path)
            || (in_array($path, ['/open', '/uc'], true) && is_string($query['id'] ?? null) && preg_match('/^[A-Za-z0-9_-]+$/', $query['id']))
        );
        $document = $host === 'docs.google.com' && preg_match('~^/(document|spreadsheets|presentation|drawings|forms)/d/[A-Za-z0-9_-]+(?:/|$)~', $path);
        if (! $driveFile && ! $document) {
            $fail('Tautan harus mengarah ke file Google Drive atau Google Docs, bukan folder atau situs lain.');
        }
    }
}
