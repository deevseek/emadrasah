<?php

declare(strict_types=1);

namespace App\Services\Academic\Imports;

use App\Models\Classroom;
use App\Models\Employee;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Collection;

final class ImportMatcher
{
    public const OFFICIAL_CLASSROOMS = ["I As-Salam (Fullday)", 'I Ar-Rahman', 'I Ar-Rahim', "II Al-Mu'min", 'II Al-Wahhab', 'III Al-Khaliq', 'III Al-Lathif', 'IV Al-Basith', 'IV Al-Karim', "V Al-'Alim", 'V Al-Hakim', 'VI Al-Majid'];

    private const SUBJECT_ALIASES = [
        'alquranhadits' => 'alquranhadis',
        'aqidahakhlaq' => 'akidahakhlak',
        'fiqih' => 'fikih',
        'pp' => 'pkn',
        'bin' => 'bindo',
        'ld' => 'tik',
        'knu' => 'kenuan',
        'big' => 'bing',
        'bjw' => 'baja',
        'lug' => 'la',
        'bindonesia' => 'bahasaindonesia',
        'barab' => 'bahasaarab',
        'binggris' => 'bahasainggris',
        'bjawa' => 'bahasajawa',
    ];

    public function normalize(?string $value): string
    {
        $value = str((string) $value)->trim()->lower()->ascii()->toString();
        $value = preg_replace('/^kelas\s+/u', '', $value) ?? $value;
        $value = str_replace('full day', 'fullday', $value);
        $value = preg_replace('/\s+fullday$/u', '', $value) ?? $value;

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    }

    public function employee(?string $number, ?string $name): array
    {
        $query = Employee::where('is_active', true);
        $found = filled($number)
            ? $query->where('employee_number', trim((string) $number))->get()
            : $query->get()->filter(fn (Employee $employee): bool => $this->normalize($employee->name) === $this->normalize($name));

        return $this->result($found, 'employee');
    }

    public function classroom(int $year, ?string $code, ?string $name): array
    {
        $query = Classroom::where('academic_year_id', $year)->where('is_active', true);
        $found = filled($code)
            ? (clone $query)->get()->filter(fn (Classroom $classroom): bool => $this->normalize($classroom->code) === $this->normalize($code))
            : new Collection;

        if ($found->isEmpty()) {
            $found = $query->get()->filter(fn (Classroom $classroom): bool => $this->normalize($classroom->name) === $this->normalize($name));
        }

        return $this->result($found, 'classroom');
    }

    public function subject(?string $code, ?string $name, ?int $gradeLevelId = null): array
    {
        $query = Subject::with('gradeLevels')->where('is_active', true);
        $subjects = $query->get();
        $found = new Collection;

        // Match in a deterministic order: code, name, short name, then known aliases.
        if (filled($code)) {
            $targetCode = $this->normalize($code);
            $found = $subjects->filter(fn (Subject $subject): bool => $this->normalize($subject->code) === $targetCode);
        }

        if ($found->isEmpty() && filled($name)) {
            $targetName = $this->normalize($name);
            $found = $subjects->filter(fn (Subject $subject): bool => $this->normalize($subject->name) === $targetName);
        }

        if ($found->isEmpty() && filled($name)) {
            $targetName = $this->normalize($name);
            $found = $subjects->filter(fn (Subject $subject): bool => $this->normalize($subject->short_name) === $targetName);
        }

        if ($found->isEmpty()) {
            $targets = collect([$code, $name])->filter()->map(fn (string $value): string => $this->subjectAlias($value))->unique();
            $found = $subjects->filter(function (Subject $subject) use ($targets): bool {
                return collect([$subject->code, $subject->name, $subject->short_name])
                    ->filter()
                    ->map(fn (string $value): string => $this->subjectAlias($value))
                    ->contains(fn (string $value): bool => $targets->contains($value));
            });
        }

        if ($gradeLevelId) {
            $found = $found->filter(fn (Subject $subject): bool => $subject->gradeLevels->isEmpty() || $subject->gradeLevels->contains($gradeLevelId));
        }

        return $this->result($found, 'subject');
    }

    private function subjectAlias(string $value): string
    {
        $normalized = $this->normalize($value);

        return self::SUBJECT_ALIASES[$normalized] ?? $normalized;
    }

    private function result(Collection $items, string $type): array
    {
        return $items->count() === 1
            ? ['model' => $items->first(), 'status' => null]
            : ['model' => null, 'status' => $items->isEmpty() ? "{$type}_not_found" : "{$type}_ambiguous"];
    }
}
