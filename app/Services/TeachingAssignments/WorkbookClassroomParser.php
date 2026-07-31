<?php

declare(strict_types=1);

namespace App\Services\TeachingAssignments;

use App\Models\Classroom;
use Illuminate\Support\Collection;

class WorkbookClassroomParser
{
    public function __construct(private WorkbookNameMatcher $matcher) {}

    public function match(?string $label, iterable $classrooms): array
    {
        $candidates = Collection::make($classrooms);
        $needle = $this->normalize($label);
        $matches = $candidates->filter(fn (Classroom $classroom): bool => collect([$classroom->name, $classroom->code, $classroom->display_name])->filter()->contains(fn ($value) => $this->normalize((string) $value) === $needle))->values();

        return match ($matches->count()) {
            0 => ['status' => 'unmatched', 'match' => null, 'matches' => $matches],
            1 => ['status' => 'matched', 'match' => $matches->first(), 'matches' => $matches],
            default => ['status' => 'selection', 'match' => null, 'matches' => $matches],
        };
    }

    public function expand(string $text, iterable $classrooms): Collection
    {
        $classrooms = Collection::make($classrooms);
        if (! preg_match('/,|&|\bdan\b/iu', $text)) {
            return $this->match($text, $classrooms)['matches'];
        }
        $numbers = $this->gradeNumbers($text);
        if ($numbers !== []) return $classrooms->filter(fn (Classroom $classroom): bool => in_array((int) $classroom->gradeLevel?->number, $numbers, true))->values();

        $parts = preg_split('/\s*(?:,|&|\bdan\b)\s*/iu', preg_replace('/^kelas\s+/iu', '', trim($text)));
        return collect($parts)->flatMap(function (string $part) use ($classrooms) {
            $result = $this->match($part, $classrooms);
            return $result['matches'];
        })->unique('id')->values();
    }

    public function normalize(?string $value): string
    {
        return $this->matcher->normalize(preg_replace('/^kelas\s+/iu', '', trim((string) $value)));
    }

    private function gradeNumbers(string $text): array
    {
        preg_match_all('/\b(?:VI|IV|V|III|II|I|[1-6])\b/iu', $text, $matches);
        $roman = ['I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4, 'V' => 5, 'VI' => 6];
        return collect($matches[0])->map(fn ($value) => $roman[strtoupper($value)] ?? (int) $value)->filter(fn ($number) => $number >= 1 && $number <= 6)->unique()->values()->all();
    }
}
