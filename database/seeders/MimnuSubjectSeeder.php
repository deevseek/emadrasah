<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\SubjectCategory;
use App\Models\GradeLevel;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class MimnuSubjectSeeder extends Seeder
{
    private const INCOMPLETE_GRADE_LEVELS_MESSAGE = 'GradeLevel 1–6 belum lengkap. Lengkapi tingkat kelas sebelum menjalankan MimnuSubjectSeeder.';

    /** @var array<string, list<string>> */
    private const ALIASES = [
        'QH' => ['QH', 'Al Qur`an Hadis', "Al Qur'an Hadis", 'Al-Qur’an Hadits', 'Al Quran Hadis', 'Al Quran Hadits'],
        'AA' => ['AA', 'Akidah Akhlak', 'Aqidah Akhlaq'],
        'FIQ' => ['FIQ', 'Fikih', 'Fiqih'],
        'PKN' => ['PKN', 'PP', 'Pendidikan Pancasila', 'Pend. Pancasila'],
        'BINDO' => ['BINDO', 'BIN', 'Bahasa Indonesia', 'B. Indonesia'],
        'TIK' => ['TIK', 'LD', 'Literasi Digital'],
        'KE-NU-AN' => ['KE-NU-AN', 'Ke-NU-an', 'Ke Nu an', 'KNU', 'KeNUan'],
        'SBDP' => ['SBDP', 'SBdP', 'Seni Budaya dan Prakarya'],
        'BING' => ['BING', 'BIG', 'Bahasa Inggris', 'B. Inggris'],
        'BAJA' => ['BAJA', 'BJW', 'Bahasa Jawa', 'B. Jawa'],
        'BTAQ' => ['BTAQ', 'Baca Tulis Al Qur`an', "Baca Tulis Al Qur'an"],
        'LA' => ['LA', 'LUG', 'Lughoh Arobiyah', 'Lughoh Arabiyah'],
        'TAQ' => ['TAQ', 'Takhassus Al-Qur’an', "Takhassus Al-Qur'an"],
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $gradeLevels = GradeLevel::query()->whereBetween('level', [1, 6])->get()->keyBy('level');

            if ($gradeLevels->count() !== 6 || collect(range(1, 6))->contains(fn (int $level): bool => ! $gradeLevels->has($level))) {
                throw new RuntimeException(self::INCOMPLETE_GRADE_LEVELS_MESSAGE);
            }

            foreach ($this->subjects() as $sortOrder => $data) {
                $levels = $data['levels'];
                unset($data['levels']);

                $subject = $this->findExistingSubject($data);
                $subject->fill($data + [
                    'description' => null,
                    'minimum_passing_grade' => 75,
                    'is_active' => true,
                    'sort_order' => $sortOrder + 1,
                ])->save();

                $subject->gradeLevels()->sync(collect($levels)->map(fn (int $level): int => (int) $gradeLevels[$level]->id)->all());
            }
        });
    }

    /** @param array{code: string, name: string, short_name: string} $data */
    private function findExistingSubject(array $data): Subject
    {
        $canonical = Subject::query()->get()->first(
            fn (Subject $subject): bool => mb_strtolower($subject->code) === mb_strtolower($data['code'])
        );

        if ($canonical) {
            return $canonical;
        }

        $aliases = collect(self::ALIASES[$data['code']] ?? [])
            ->push($data['name'], $data['short_name'])
            ->map(fn (string $value): string => $this->normalize($value))
            ->unique();

        $legacy = Subject::query()->get()->first(function (Subject $subject) use ($aliases): bool {
            return collect([$subject->code, $subject->name, $subject->short_name])
                ->filter()
                ->map(fn (string $value): string => $this->normalize($value))
                ->contains(fn (string $value): bool => $aliases->contains($value));
        });

        if ($legacy) {
            $conflict = Subject::query()->whereKeyNot($legacy->getKey())->get()->first(
                fn (Subject $subject): bool => mb_strtolower($subject->code) === mb_strtolower($data['code'])
            );
            if ($conflict) {
                throw new RuntimeException("Konflik kode mata pelajaran {$data['code']}: telah digunakan oleh Subject ID {$conflict->id}.");
            }

            return $legacy;
        }

        return new Subject;
    }

    private function normalize(string $value): string
    {
        $value = str($value)->lower()->ascii()->toString();

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    }

    /** @return list<array{code: string, name: string, short_name: string, category: SubjectCategory, default_weekly_hours: int, levels: list<int>}> */
    private function subjects(): array
    {
        $all = [1, 2, 3, 4, 5, 6];

        return [
            ['code' => 'QH', 'name' => "Al Qur'an Hadis", 'short_name' => 'QH', 'category' => SubjectCategory::Religion, 'default_weekly_hours' => 2, 'levels' => $all],
            ['code' => 'AA', 'name' => 'Akidah Akhlak', 'short_name' => 'AA', 'category' => SubjectCategory::Religion, 'default_weekly_hours' => 2, 'levels' => $all],
            ['code' => 'FIQ', 'name' => 'Fikih', 'short_name' => 'Fikih', 'category' => SubjectCategory::Religion, 'default_weekly_hours' => 2, 'levels' => $all],
            ['code' => 'SKI', 'name' => 'Sejarah Kebudayaan Islam', 'short_name' => 'SKI', 'category' => SubjectCategory::Religion, 'default_weekly_hours' => 2, 'levels' => [3, 4, 5, 6]],
            ['code' => 'BAR', 'name' => 'Bahasa Arab', 'short_name' => 'BAR', 'category' => SubjectCategory::Religion, 'default_weekly_hours' => 2, 'levels' => $all],
            ['code' => 'PKN', 'name' => 'Pendidikan Pancasila', 'short_name' => 'Pend. Pancasila', 'category' => SubjectCategory::General, 'default_weekly_hours' => 4, 'levels' => $all],
            ['code' => 'BINDO', 'name' => 'Bahasa Indonesia', 'short_name' => 'B. Indonesia', 'category' => SubjectCategory::General, 'default_weekly_hours' => 6, 'levels' => $all],
            ['code' => 'MTK', 'name' => 'Matematika', 'short_name' => 'MTK', 'category' => SubjectCategory::General, 'default_weekly_hours' => 4, 'levels' => $all],
            ['code' => 'IPAS', 'name' => 'Ilmu Pengetahuan Alam dan Sosial', 'short_name' => 'IPAS', 'category' => SubjectCategory::General, 'default_weekly_hours' => 5, 'levels' => [3, 4, 5, 6]],
            ['code' => 'PJOK', 'name' => 'Pendidikan Jasmani, Olahraga dan Kesehatan', 'short_name' => 'PJOK', 'category' => SubjectCategory::General, 'default_weekly_hours' => 2, 'levels' => $all],
            ['code' => 'SBDP', 'name' => 'Seni Budaya dan Prakarya', 'short_name' => 'SBdP', 'category' => SubjectCategory::General, 'default_weekly_hours' => 2, 'levels' => $all],
            ['code' => 'BING', 'name' => 'Bahasa Inggris', 'short_name' => 'B. Inggris', 'category' => SubjectCategory::General, 'default_weekly_hours' => 1, 'levels' => $all],
            ['code' => 'BAJA', 'name' => 'Bahasa Jawa', 'short_name' => 'B. Jawa', 'category' => SubjectCategory::LocalContent, 'default_weekly_hours' => 1, 'levels' => $all],
            ['code' => 'BTAQ', 'name' => "Baca Tulis Al Qur'an", 'short_name' => 'BTAQ', 'category' => SubjectCategory::Btaq, 'default_weekly_hours' => 10, 'levels' => $all],
            ['code' => 'TIK', 'name' => 'Literasi Digital (TIK, Koding dan Kecerdasan Artifisial)', 'short_name' => 'Literasi Digital', 'category' => SubjectCategory::General, 'default_weekly_hours' => 2, 'levels' => $all],
            ['code' => 'KE-NU-AN', 'name' => 'Ke Nu an', 'short_name' => 'Ke-NU-an', 'category' => SubjectCategory::LocalContent, 'default_weekly_hours' => 1, 'levels' => $all],
            ['code' => 'TKA', 'name' => 'TKA', 'short_name' => 'TKA', 'category' => SubjectCategory::Other, 'default_weekly_hours' => 2, 'levels' => [6]],
            ['code' => 'TAQ', 'name' => "Takhassus Al-Qur'an", 'short_name' => 'TAQ', 'category' => SubjectCategory::Btaq, 'default_weekly_hours' => 12, 'levels' => [1]],
            ['code' => 'NUM', 'name' => 'Numerasi', 'short_name' => 'Numerasi', 'category' => SubjectCategory::SelfDevelopment, 'default_weekly_hours' => 2, 'levels' => [1]],
            ['code' => 'LIT', 'name' => 'Literasi', 'short_name' => 'Literasi', 'category' => SubjectCategory::SelfDevelopment, 'default_weekly_hours' => 2, 'levels' => [1]],
            ['code' => 'LA', 'name' => 'Lughoh Arobiyah', 'short_name' => 'Lughoh Arobiyah', 'category' => SubjectCategory::Religion, 'default_weekly_hours' => 2, 'levels' => [1]],
            ['code' => 'STEAM', 'name' => 'Science, Technology, Engineering, Arts, and Mathematics (STEAM)', 'short_name' => 'STEAM', 'category' => SubjectCategory::SelfDevelopment, 'default_weekly_hours' => 1, 'levels' => [1, 2, 3, 4, 5]],
        ];
    }
}
