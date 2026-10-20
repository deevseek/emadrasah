<?php

declare(strict_types=1);

namespace Tests\Feature\Classrooms;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\GradeLevel;
use App\Models\Personnel;
use App\Models\User;
use App\Services\Classrooms\ClassroomService;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ClassroomHomeroomSwapTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $this->seed(AccessControlSeeder::class);
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo('classrooms.assign-homeroom');
        $year = AcademicYear::create(['name' => '2026/2027', 'starts_at' => '2026-07-01', 'ends_at' => '2027-06-30', 'is_active' => true]);
        $grade = GradeLevel::create(['number' => 1, 'name' => 'Kelas 1', 'roman_label' => 'I', 'sort_order' => 1]);
        $rooms = [];
        foreach (['AS-SALAM', 'AR-RAHMAN', 'AR-RAHIM'] as $index => $name) {
            $teacher = Personnel::create(['full_name' => 'Guru '.$name, 'gender' => 'female', 'employment_status' => 'permanent', 'position' => 'Guru Kelas', 'is_active' => true]);
            $rooms[] = Classroom::create(['academic_year_id' => $year->id, 'grade_level_id' => $grade->id, 'code' => (string) $index, 'name' => $name, 'homeroom_personnel_id' => $teacher->id, 'is_active' => true]);
        }

        return [$user, ...$rooms];
    }

    public function test_swap_updates_both_rooms_and_records_history_without_changing_other_room(): void
    {
        [$user, $source, $target, $other] = $this->context();
        $sourceTeacher = $source->homeroom_personnel_id;
        $targetTeacher = $target->homeroom_personnel_id;
        $otherTeacher = $other->homeroom_personnel_id;
        $this->actingAs($user)->get(route('classrooms.homeroom.edit', $source))
            ->assertOk()->assertSee('Tukar Wali Kelas Antarrombel')->assertSee($target->homeroom_name);
        $this->patch(route('classrooms.homeroom.swap', $source), ['target_classroom_id' => (string) $target->id])
            ->assertRedirect(route('classrooms.homeroom.edit', $source))->assertSessionHasNoErrors();
        $this->assertSame($targetTeacher, $source->refresh()->homeroom_personnel_id);
        $this->assertSame($sourceTeacher, $target->refresh()->homeroom_personnel_id);
        $this->assertSame($otherTeacher, $other->refresh()->homeroom_personnel_id);
        foreach ([$source, $target] as $room) {
            $this->assertSame($user->id, $room->updated_by);
            $this->assertTrue(Activity::where('subject_type', Classroom::class)->where('subject_id', $room->id)
                ->where('description', 'Menukar wali kelas antarrombel.')->exists());
        }
    }

    public function test_regular_assignment_still_rejects_an_already_assigned_teacher(): void
    {
        [$user, $source, $target] = $this->context();
        $old = $source->homeroom_personnel_id;
        $this->actingAs($user)->patch(route('classrooms.homeroom.update', $source), ['homeroom_personnel_id' => $target->homeroom_personnel_id])
            ->assertSessionHasErrors('homeroom_personnel_id');
        $this->assertSame($old, $source->refresh()->homeroom_personnel_id);
    }

    public function test_swap_requires_permission(): void
    {
        [$user, $source, $target] = $this->context();
        $user->revokePermissionTo('classrooms.assign-homeroom');
        $this->actingAs($user)->patch(route('classrooms.homeroom.swap', $source), ['target_classroom_id' => $target->id])->assertForbidden();
    }

    public function test_invalid_swap_leaves_both_assignments_unchanged(): void
    {
        [$user, $source, $target] = $this->context();
        $sourceTeacher = $source->homeroom_personnel_id;
        $targetTeacher = $target->homeroom_personnel_id;
        $this->actingAs($user);
        $this->patch(route('classrooms.homeroom.swap', $source), ['target_classroom_id' => $source->id])
            ->assertSessionHasErrors('target_classroom_id');
        $target->update(['is_active' => false]);
        $this->patch(route('classrooms.homeroom.swap', $source), ['target_classroom_id' => $target->id])
            ->assertSessionHasErrors('target_classroom_id');
        $year = AcademicYear::create(['name' => '2027/2028', 'starts_at' => '2027-07-01', 'ends_at' => '2028-06-30']);
        $target->update(['is_active' => true, 'academic_year_id' => $year->id]);
        $this->patch(route('classrooms.homeroom.swap', $source), ['target_classroom_id' => $target->id])
            ->assertSessionHasErrors('target_classroom_id');
        $this->assertSame($sourceTeacher, $source->refresh()->homeroom_personnel_id);
        $this->assertSame($targetTeacher, $target->refresh()->homeroom_personnel_id);
    }

    public function test_empty_or_ineligible_homeroom_cannot_be_swapped(): void
    {
        [$user, $source, $target] = $this->context();
        $sourceTeacher = $source->homeroom_personnel_id;
        $targetTeacher = $target->homeroom_personnel_id;
        $target->update(['homeroom_personnel_id' => null]);
        $this->actingAs($user)->patch(route('classrooms.homeroom.swap', $source), ['target_classroom_id' => $target->id])
            ->assertSessionHasErrors('target_classroom_id');
        $target->update(['homeroom_personnel_id' => $targetTeacher]);
        $target->homeroomPersonnel()->update(['is_active' => false]);
        $this->patch(route('classrooms.homeroom.swap', $source), ['target_classroom_id' => $target->id])
            ->assertSessionHasErrors('homeroom_personnel_id');
        $this->assertSame($sourceTeacher, $source->refresh()->homeroom_personnel_id);
        $this->assertSame($targetTeacher, $target->refresh()->homeroom_personnel_id);
    }

    public function test_failure_saving_second_room_rolls_back_first_room(): void
    {
        [$user, $source, $target] = $this->context();
        $sourceTeacher = $source->homeroom_personnel_id;
        $targetTeacher = $target->homeroom_personnel_id;
        $event = 'eloquent.updating: '.Classroom::class;
        Event::listen($event, function (Classroom $room) use ($target): void {
            if ($room->id === $target->id) {
                throw new RuntimeException('Simulasi kegagalan penyimpanan rombel kedua.');
            }
        });
        try {
            app(ClassroomService::class)->swapHomerooms($user, $source, $target->id);
            $this->fail('Pertukaran harus gagal dan dibatalkan seluruhnya.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulasi kegagalan penyimpanan rombel kedua.', $exception->getMessage());
        } finally {
            Event::forget($event);
        }
        $this->assertSame($sourceTeacher, $source->refresh()->homeroom_personnel_id);
        $this->assertSame($targetTeacher, $target->refresh()->homeroom_personnel_id);
        $this->assertFalse(Activity::where('description', 'Menukar wali kelas antarrombel.')->exists());
    }
}
