<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AcademicSubject;
use Illuminate\Database\Seeder;

class AcademicSubjectSeeder extends Seeder
{
    public function run(): void
    {
        // Jadwal kelas I As-Salam (fullday), semester ganjil 2026/2027.
        $subjects = [
            ['BTAQ', 'BTAQ'],
            ['PANCASILA', 'Pendidikan Pancasila'],
            ['QH', 'Al-Qur’an Hadits'],
            ['BIND', 'Bahasa Indonesia'],
            ['MTK', 'Matematika'],
            ['AA', 'Aqidah Akhlaq'],
            ['SBDP', 'SBdP'],
            ['FIQIH', 'Fiqih'],
            ['PJOK', 'PJOK'],
            ['BING', 'Bahasa Inggris'],
            ['BJAWA', 'Bahasa Jawa'],
            ['BARAB', 'Bahasa Arab'],
            ['KENUAN', 'Ke-NU-an'],
            ['LITDIG', 'Literasi Digital (TIK, Koding dan Kecerdasan Artifisial)'],
            ['TAKHASSUS', 'Takhassus Al-Qur’an'],
            ['NUMERASI', 'Numerasi'],
            ['LITERASI', 'Literasi'],
            ['LUGHOH', 'Lughoh Arobiyah'],
            ['STEAM', 'Science, Technology, Engineering, Arts, and Mathematics (STEAM)'],
        ];

        foreach ($subjects as $index => [$code, $name]) {
            // Pertahankan pengaturan mata pelajaran yang sudah dibuat operator.
            AcademicSubject::firstOrCreate(
                ['name' => $name],
                ['code' => $code, 'is_active' => true, 'sort_order' => $index + 1],
            );
        }
    }
}
