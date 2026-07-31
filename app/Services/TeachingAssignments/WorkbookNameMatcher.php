<?php

declare(strict_types=1);

namespace App\Services\TeachingAssignments;

use Illuminate\Support\Collection;

class WorkbookNameMatcher
{
    public function normalize(?string $value): string
    {
        $value = str_replace(["’", "‘", "`", "´"], "'", trim((string) $value));
        $value = preg_replace('/\.(?=\s|$)/u', '', $value);
        $value = preg_replace('/[-‐‑‒–—]+/u', ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value);

        return mb_strtolower(trim((string) $value));
    }

    public function match(?string $workbookName, iterable $candidates, string $attribute = 'full_name'): array
    {
        $needle = $this->normalize($workbookName);
        $matches = Collection::make($candidates)->filter(fn ($candidate): bool => $needle !== '' && $this->normalize((string) data_get($candidate, $attribute)) === $needle)->values();

        return match ($matches->count()) {
            0 => ['status' => 'unmatched', 'match' => null, 'matches' => $matches],
            1 => ['status' => 'matched', 'match' => $matches->first(), 'matches' => $matches],
            default => ['status' => 'selection', 'match' => null, 'matches' => $matches],
        };
    }
}
