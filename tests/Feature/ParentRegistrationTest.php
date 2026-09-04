<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\GuardianRegistrationMail;
use App\Events\GuardianRegistered;
use App\Listeners\SendGuardianRegistrationEmail;
use App\Models\GuardianProfile;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ParentRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
    }

    public function test_parent_can_open_registration_page_from_login(): void
    {
        $this->get(route('parent.login'))
            ->assertOk()
            ->assertSee(route('parent.register'));

        $this->get(route('parent.register'))
            ->assertOk()
            ->assertSee('Daftarkan Akun Anda')
            ->assertSee(route('parent.register.store'));
    }

    public function test_registration_creates_parent_account_links_child_and_sends_confirmation_email(): void
    {
        Event::fake([GuardianRegistered::class]);
        $student = $this->student();

        $response = $this->post(route('parent.register.store'), [
            'name' => '  Siti Aminah  ',
            'email' => 'IBU.AMINAH@EXAMPLE.COM',
            'phone_number' => '0812 3456 7890',
            'relationship' => 'mother',
            'nisn' => $student->nisn,
            'birth_date' => $student->birth_date->format('Y-m-d'),
            'password' => 'Rahasia123!',
            'password_confirmation' => 'Rahasia123!',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('parent.login'))->assertSessionHas('status');
        $user = User::query()->where('email', 'ibu.aminah@example.com')->firstOrFail();
        $guardian = GuardianProfile::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertTrue($user->hasRole('orang-tua'));
        $this->assertTrue(Hash::check('Rahasia123!', $user->password));
        $this->assertDatabaseHas('student_guardians', [
            'guardian_id' => $guardian->id,
            'student_id' => $student->id,
            'relationship' => 'mother',
            'can_view_finance' => true,
        ]);
        Event::assertDispatched(GuardianRegistered::class, fn (GuardianRegistered $event): bool =>
            $event->guardian->is($guardian) && $event->student->is($student)
        );

        Mail::fake();
        app(SendGuardianRegistrationEmail::class)->handle(new GuardianRegistered($guardian, $student));
        Mail::assertSent(GuardianRegistrationMail::class, fn (GuardianRegistrationMail $mail): bool =>
            $mail->hasTo('ibu.aminah@example.com') && $mail->student->is($student));
    }

    public function test_registration_rejects_child_identity_that_does_not_match_without_partial_account(): void
    {
        Mail::fake();
        $student = $this->student();

        $this->from(route('parent.register'))->post(route('parent.register.store'), [
            'name' => 'Ahmad Fauzi',
            'email' => 'ahmad@example.com',
            'phone_number' => '081234567890',
            'relationship' => 'father',
            'nisn' => $student->nisn,
            'birth_date' => '2013-01-01',
            'password' => 'Rahasia123!',
            'password_confirmation' => 'Rahasia123!',
            'terms' => '1',
        ])->assertRedirect(route('parent.register'))->assertSessionHasErrors('nisn');

        $this->assertDatabaseMissing('users', ['email' => 'ahmad@example.com']);
        $this->assertDatabaseCount('guardian_profiles', 0);
        Mail::assertNothingSent();
    }

    public function test_inactive_student_cannot_be_claimed_during_registration(): void
    {
        $student = $this->student(['status' => 'graduated']);

        $this->post(route('parent.register.store'), [
            'name' => 'Wali Siswa',
            'email' => 'wali@example.com',
            'phone_number' => '081234567890',
            'relationship' => 'guardian',
            'nisn' => $student->nisn,
            'birth_date' => $student->birth_date->format('Y-m-d'),
            'password' => 'Rahasia123!',
            'password_confirmation' => 'Rahasia123!',
            'terms' => '1',
        ])->assertSessionHasErrors('nisn');

        $this->assertDatabaseMissing('users', ['email' => 'wali@example.com']);
    }

    /** @param array<string, mixed> $overrides */
    private function student(array $overrides = []): Student
    {
        return Student::create(array_merge([
            'full_name' => 'Muhammad Hasan',
            'nisn' => '0012345678',
            'birth_date' => '2014-06-15',
            'status' => 'active',
            'gender' => 'male',
        ], $overrides));
    }
}
