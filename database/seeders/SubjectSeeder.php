<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ClassroomProgramType;
use App\Models\{GradeLevel, Subject, SubjectGradeLoad};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['QH', 'Al-Qur’an Hadis', 'Keagamaan', [2,2,2,2,2,2,2]], ['AA', 'Akidah Akhlak', 'Keagamaan', [2,2,2,2,2,2,2]],
            ['FIK', 'Fikih', 'Keagamaan', [2,2,2,2,2,2,2]], ['SKI', 'Sejarah Kebudayaan Islam', 'Keagamaan', [2,2,2,2,2,2,2]],
            ['BAR', 'Bahasa Arab', 'Keagamaan', [2,2,2,2,2,2,2]], ['PNC', 'Pendidikan Pancasila', 'Umum', [4,4,4,4,4,4,4]],
            ['BIN', 'Bahasa Indonesia', 'Umum', [8,8,9,7,7,7,7]], ['MAT', 'Matematika', 'Umum', [6,6,6,6,6,6,6]],
            ['IPAS', 'IPAS', 'Umum', [null,null,null,5,5,5,5]], ['SBDP', 'Seni Budaya', 'Umum', [4,4,4,4,4,4,4]],
            ['PJOK', 'Pendidikan Jasmani, Olahraga, dan Kesehatan', 'Umum', [4,4,3,3,3,3,3]], ['BJW', 'Bahasa Jawa', 'Muatan Lokal', [2,2,2,2,2,2,2]],
            ['BIG', 'Bahasa Inggris', 'Muatan Lokal', [2,2,2,2,2,2,null]], ['NU', 'Ke-NU-an', 'Muatan Lokal', [2,2,2,2,2,2,2]],
            ['BTQ', 'Baca Tulis Al-Qur’an', 'Program Madrasah', [4,null,null,2,2,2,2]], ['THF', 'Tahfiz Al-Qur’an', 'Program Madrasah', [6,null,null,3,3,3,3]],
            ['TIK', 'Teknologi Informasi dan Komunikasi', 'Program Madrasah', [2,null,null,null,null,null,null]], ['DHA', 'Pembiasaan Salat Duha', 'Program Madrasah', [2,null,null,null,null,null,null]],
            ['PBI', 'Pembiasaan Ibadah', 'Program Madrasah', [2,null,null,null,null,null,null]], ['PRM', 'Pramuka', 'Ekstrakurikuler', [2,null,null,null,null,null,null]],
            ['PDI', 'Takhassus Al-Qur’an', 'Program Madrasah', [2,null,null,null,null,null,null]], ['KLG', 'TKA', 'Program Madrasah', [null,null,null,null,null,null,2]],
        ];
        $columns = [[1, ClassroomProgramType::FullDay], [1, ClassroomProgramType::Regular], [2, ClassroomProgramType::Regular], [3, ClassroomProgramType::Regular], [4, ClassroomProgramType::Regular], [5, ClassroomProgramType::Regular], [6, ClassroomProgramType::Regular]];
        DB::transaction(function () use ($rows, $columns): void {
            foreach ($rows as $order => [, $name, $category, $hours]) {
                $subject = Subject::updateOrCreate(['sort_order' => $order + 1], ['code' => chr(65 + $order), 'name' => $name, 'category' => $category, 'is_active' => true]);
                foreach ($columns as $index => [$gradeNumber, $program]) {
                    $grade = GradeLevel::where('number', $gradeNumber)->firstOrFail();
                    $attributes = ['subject_id' => $subject->id, 'grade_level_id' => $grade->id, 'program_type' => $program->value];
                    $hours[$index] === null ? SubjectGradeLoad::where($attributes)->delete() : SubjectGradeLoad::updateOrCreate($attributes, ['weekly_hours' => $hours[$index]]);
                }
            }
        });
    }
}
