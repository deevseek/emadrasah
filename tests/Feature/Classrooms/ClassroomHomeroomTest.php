<?php

declare(strict_types=1);

namespace Tests\Feature\Classrooms;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\GradeLevel;
use App\Models\Personnel;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassroomHomeroomTest extends TestCase
{
    use RefreshDatabase;

    public function test_homeroom_can_be_assigned_from_a_form_encoded_personnel_id(): void
    {
        $this->seed(AccessControlSeeder::class);

        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo('classrooms.assign-homeroom');

        $year = AcademicYear::create([
            'name' => '2026/2027',
            'starts_at' => '2026-07-01',
            'ends_at' => '2027-06-30',
            'is_active' => true,
        ]);
        $grade = GradeLevel::create([
            'number' => 2,
            'name' => 'Kelas 2',
            'roman_label' => 'II',
            'sort_order' => 2,
        ]);
        $classroom = Classroom::create([
            'academic_year_id' => $year->id,
            'grade_level_id' => $grade->id,
            'code' => 'II A',
            'is_active' => true,
        ]);
        $personnel = Personnel::create([
            'full_name' => 'Guru Kelas Dua',
            'gender' => 'female',
            'employment_status' => 'permanent',
            'position' => 'Guru Kelas',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->patch(route('classrooms.homeroom.update', $classroom), [
                'homeroom_personnel_id' => (string) $personnel->id,
            ])
            ->assertRedirect(route('classrooms.show', $classroom))
            ->assertSessionHas('success', 'Wali kelas berhasil diperbarui.');

        $this->assertDatabaseHas('classrooms', [
            'id' => $classroom->id,
            'homeroom_personnel_id' => $personnel->id,
            'updated_by' => $user->id,
        ]);
    }
}
