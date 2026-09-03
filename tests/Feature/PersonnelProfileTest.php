<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\FaceRecognitionService;
use App\Models\Personnel;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class PersonnelProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
    }

    public function test_login_redirects_users_to_their_role_destination(): void
    {
        foreach ([
            'orang-tua' => '/parent',
            'bendahara' => '/finance',
            'hrd' => '/hrd',
        ] as $role => $destination) {
            $user = User::factory()->create([
                'email' => $role.'@example.test',
                'password' => Hash::make('rahasia'),
                'must_change_password' => false,
            ]);
            $user->syncRoles([$role]);

            $this->post('/login', ['login' => $user->email, 'password' => 'rahasia'])
                ->assertRedirect($destination);
            $this->post('/logout');
        }
    }

    public function test_teacher_with_linked_personnel_is_redirected_to_own_profile(): void
    {
        $user = User::factory()->create([
            'email' => 'guru@example.test',
            'password' => Hash::make('rahasia'),
            'must_change_password' => false,
        ]);
        $user->syncRoles(['guru']);
        $this->personnel($user);

        $this->post('/login', ['login' => $user->email, 'password' => 'rahasia'])
            ->assertRedirect(route('personnel.profile.show'));
    }

    public function test_linked_user_can_view_only_their_own_account_and_personnel_information(): void
    {
        $user = User::factory()->create(['name' => 'Siti Aminah', 'username' => 'siti.aminah', 'must_change_password' => false]);
        $user->syncRoles(['guru']);
        $this->personnel($user, ['full_name' => 'Siti Aminah, S.Pd.', 'position' => 'Guru Matematika', 'nip' => '19870001']);

        $this->actingAs($user)->get(route('personnel.profile.show'))
            ->assertOk()
            ->assertSee('Informasi Akun')
            ->assertSee('siti.aminah')
            ->assertSee('Siti Aminah, S.Pd.')
            ->assertSee('Guru Matematika')
            ->assertSee('Daftarkan Wajah')
            ->assertSee('navigator.mediaDevices.getUserMedia', false);

        $unlinked = User::factory()->create(['must_change_password' => false]);
        $this->actingAs($unlinked)->get(route('personnel.profile.show'))->assertNotFound();
    }

    public function test_active_personnel_can_enroll_their_own_face(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['must_change_password' => false]);
        $user->syncRoles(['guru']);
        $personnel = $this->personnel($user);
        $faces = Mockery::mock(FaceRecognitionService::class);
        $faces->shouldReceive('encode')->times(3)->andReturn([
            'embedding' => [0.1, 0.2],
            'quality_score' => 0.95,
            'model' => 'test-model',
            'model_version' => '1',
        ]);
        $faces->shouldReceive('provider')->once()->andReturn('test');
        $this->app->instance(FaceRecognitionService::class, $faces);

        $response = $this->actingAs($user)->post(route('personnel.profile.face.enroll'), [
            'front' => UploadedFile::fake()->image('front.jpg'),
            'left' => UploadedFile::fake()->image('left.jpg'),
            'right' => UploadedFile::fake()->image('right.jpg'),
        ]);

        $response->assertRedirect()->assertSessionHas('status', 'Wajah Anda berhasil didaftarkan.');
        $this->assertDatabaseHas('personnel_face_profiles', ['personnel_id' => $personnel->id, 'status' => 'active']);
        $this->assertDatabaseCount('personnel_face_samples', 3);
    }

    private function personnel(User $user, array $attributes = []): Personnel
    {
        return Personnel::create(array_merge([
            'full_name' => $user->name,
            'gender' => 'female',
            'employment_status' => 'permanent',
            'position' => 'Guru',
            'is_active' => true,
            'user_id' => $user->id,
        ], $attributes));
    }
}
