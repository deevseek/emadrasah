<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Student;
use App\Models\StudentRfidCard;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteStudentRfidCardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
    }

    public function test_operator_can_delete_an_active_student_rfid_card(): void
    {
        $operator = User::factory()->create(['must_change_password' => false]);
        $operator->assignRole('operator');
        $student = Student::create(['full_name' => 'Ahmad Fauzan', 'gender' => 'male', 'status' => 'active']);
        $card = $student->rfidCards()->create([
            'uid' => '04A31FB291',
            'is_active' => true,
            'registered_at' => now(),
        ]);

        $this->actingAs($operator)
            ->get(route('students.show', $student))
            ->assertOk()
            ->assertSee('Hapus Kartu')
            ->assertSee('Kartu RFID akan dihapus dan tidak dapat digunakan lagi. Lanjutkan?');

        $this->actingAs($operator)
            ->delete(route('students.rfid-card.destroy', $student))
            ->assertRedirect()
            ->assertSessionHas('status', 'Kartu RFID siswa berhasil dihapus.');

        $this->assertDatabaseMissing('student_rfid_cards', ['id' => $card->id]);
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Student::class,
            'subject_id' => $student->id,
            'description' => 'Menghapus kartu RFID siswa.',
        ]);
    }

    public function test_user_without_permission_cannot_delete_student_rfid_card(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $student = Student::create(['full_name' => 'Ahmad Fauzan', 'gender' => 'male', 'status' => 'active']);
        $card = StudentRfidCard::create(['student_id' => $student->id, 'uid' => '04A31FB291', 'is_active' => true]);

        $this->actingAs($user)
            ->delete(route('students.rfid-card.destroy', $student))
            ->assertForbidden();

        $this->assertDatabaseHas('student_rfid_cards', ['id' => $card->id]);
    }

    public function test_delete_fails_when_student_has_no_active_rfid_card(): void
    {
        $operator = User::factory()->create(['must_change_password' => false]);
        $operator->assignRole('operator');
        $student = Student::create(['full_name' => 'Ahmad Fauzan', 'gender' => 'male', 'status' => 'active']);

        $this->actingAs($operator)
            ->delete(route('students.rfid-card.destroy', $student))
            ->assertNotFound();
    }
}
