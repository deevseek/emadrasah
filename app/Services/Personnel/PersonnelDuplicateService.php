<?php

declare(strict_types=1);

namespace App\Services\Personnel;

use App\Models\Personnel;

class PersonnelDuplicateService
{
    private const IDENTIFIERS = [
        'foundation_employee_number',
        'nip',
        'external_employee_id',
        'email',
    ];

    private const PLACEHOLDERS = [
        '-',
        '–',
        '—',
        'N/A',
        'NA',
        'NULL',
        'NIHIL',
        'TIDAK ADA',
    ];

    /**
     * @return array{match: ?Personnel, conflict: bool, matched_by: ?string}
     */
    public function find(array $data): array
    {
        $matches = collect();
        $matchedBy = [];

        foreach (self::IDENTIFIERS as $key) {
            if (! $this->hasIdentifier($data[$key] ?? null)) {
                continue;
            }

            foreach (Personnel::where($key, trim((string) $data[$key]))->get() as $personnel) {
                $matches->push($personnel);
                $matchedBy[$personnel->id] ??= $key;
            }
        }

        if ($this->hasIdentifier($data['full_name'] ?? null) && ! empty($data['birth_date'])) {
            foreach (Personnel::where('full_name', trim((string) $data['full_name']))
                ->whereDate('birth_date', $data['birth_date'])
                ->get() as $personnel) {
                $matches->push($personnel);
                $matchedBy[$personnel->id] ??= 'name_and_birth_date';
            }
        }

        $unique = $matches->unique('id')->values();
        $match = $unique->count() === 1 ? $unique->first() : null;

        return [
            'match' => $match,
            'conflict' => $unique->count() > 1,
            'matched_by' => $match ? ($matchedBy[$match->id] ?? null) : null,
        ];
    }

    private function hasIdentifier(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return false;
        }

        return ! in_array(mb_strtoupper($value), self::PLACEHOLDERS, true);
    }
}
