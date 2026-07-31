<?php

declare(strict_types=1);

namespace Tests\Feature\Subjects;

use App\Enums\ClassroomProgramType;
use App\Models\{Classroom, Subject, SubjectGradeLoad, User};
use Database\Seeders\{AccessControlSeeder, AcademicPeriodSeeder, GradeLevelSeeder, SubjectSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_subject_seeder_imports_22_subjects_without_zero_loads_and_expected_totals(): void
    {
        $this->seed(GradeLevelSeeder::class); $this->seed(SubjectSeeder::class);
        $this->assertDatabaseCount('subjects', 22); $this->assertFalse(SubjectGradeLoad::where('weekly_hours', 0)->exists());
        $this->assertSame(range('A', 'V'), Subject::orderBy('sort_order')->pluck('code')->all());
        $this->assertFalse(SubjectGradeLoad::whereHas('subject', fn ($query) => $query->where('name', 'IPAS'))->whereHas('gradeLevel', fn ($query) => $query->whereIn('number', [1, 2]))->exists());
        $this->assertSame([6], SubjectGradeLoad::whereHas('subject', fn ($query) => $query->where('name', 'TKA'))->with('gradeLevel')->get()->pluck('gradeLevel.number')->all());
        $this->assertSame(['full_day'], SubjectGradeLoad::whereHas('subject', fn ($query) => $query->where('name', 'Takhassus Al-Qur’an'))->pluck('program_type')->map->value->all());
        $expected = ['1_full_day' => 62, '1_regular' => 42, '2_regular' => 42, '3_regular' => 50, '4_regular' => 50, '5_regular' => 50, '6_regular' => 50];
        foreach ($expected as $key => $total) { [$grade, $program] = explode('_', $key, 2); $actual = SubjectGradeLoad::whereHas('gradeLevel', fn ($query) => $query->where('number', $grade))->where('program_type', $program)->sum('weekly_hours'); $this->assertSame($total, (int) $actual, $key); }
    }

    public function test_classroom_program_defaults_to_regular_and_casts_to_enum(): void
    {
        $this->seed([AcademicPeriodSeeder::class, GradeLevelSeeder::class]);
        $classroom = Classroom::create(['academic_year_id' => 1, 'grade_level_id' => 1, 'code' => 'I A']);
        $this->assertSame(ClassroomProgramType::Regular, $classroom->refresh()->program_type);
    }

    public function test_authorized_user_can_manage_subject_master_and_matrix(): void
    {
        $this->seed([AccessControlSeeder::class, GradeLevelSeeder::class, SubjectSeeder::class]);
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]); $user->assignRole('super-admin');
        $this->actingAs($user)->post(route('subjects.store'), ['code' => 'UJI', 'name' => 'Mata Pelajaran Uji', 'sort_order' => 30, 'is_active' => 1])->assertRedirect(route('subjects.index'));
        $subject = Subject::where('code', 'UJI')->firstOrFail();
        $this->actingAs($user)->put(route('subjects.update', $subject), ['code' => 'UJI', 'name' => 'Mata Pelajaran Diperbarui', 'sort_order' => 30, 'is_active' => 1])->assertRedirect(route('subjects.index'));
        $this->assertDatabaseHas('subjects', ['code' => 'UJI', 'name' => 'Mata Pelajaran Diperbarui']);
        $this->actingAs($user)->put(route('subjects.loads.update'), ['loads' => ['g1_regular_'.$subject->id => 0]])->assertSessionHasErrors();
    }
}
