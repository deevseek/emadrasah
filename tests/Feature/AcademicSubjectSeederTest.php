<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicSubject;
use Database\Seeders\AcademicSubjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicSubjectSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_seeding_keeps_subject_ids_and_operator_settings(): void
    {
        $subject = AcademicSubject::create([
            'name' => 'Matematika',
            'code' => 'MTK-CUSTOM',
            'is_active' => false,
            'sort_order' => 99,
        ]);

        $this->seed(AcademicSubjectSeeder::class);
        $ids = AcademicSubject::orderBy('id')->pluck('id')->all();
        $this->seed(AcademicSubjectSeeder::class);

        $this->assertDatabaseCount('academic_subjects', 19);
        $this->assertSame($ids, AcademicSubject::orderBy('id')->pluck('id')->all());
        $this->assertDatabaseHas('academic_subjects', [
            'id' => $subject->id,
            'name' => 'Matematika',
            'code' => 'MTK-CUSTOM',
            'is_active' => false,
            'sort_order' => 99,
        ]);
        $this->assertDatabaseHas('academic_subjects', [
            'name' => 'Lughoh Arobiyah',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('academic_subjects', [
            'code' => 'BARAB',
            'name' => 'Bahasa Arab',
        ]);
        $this->assertDatabaseMissing('academic_subjects', ['name' => 'ISTIRAHAT']);
    }
}
