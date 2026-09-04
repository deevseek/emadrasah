<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_can_be_opened(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk');
    }

    public function test_parent_login_page_can_be_opened(): void
    {
        $this->get(route('parent.login'))
            ->assertOk()
            ->assertSee('Masuk Portal Orang Tua')
            ->assertSee(route('parent.login.store'));
    }

    public function test_parent_account_can_log_in_from_parent_portal(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('rahasia'),
            'must_change_password' => false,
        ]);
        $user->assignRole(Role::findOrCreate('orang-tua'));

        $this->post(route('parent.login.store'), ['login' => $user->email, 'password' => 'rahasia'])
            ->assertRedirect(route('parent.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_non_parent_account_is_rejected_from_parent_portal(): void
    {
        $user = User::factory()->create(['password' => Hash::make('rahasia')]);

        $this->from(route('parent.login'))->post(route('parent.login.store'), [
            'login' => $user->email,
            'password' => 'rahasia',
        ])->assertRedirect(route('parent.login'))->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_valid_active_user_can_log_in_and_log_out(): void
    {
        $user = User::factory()->create(['password' => Hash::make('rahasia')]);

        $this->post('/login', ['login' => $user->email, 'password' => 'rahasia'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->from('/login')->post('/login', ['login' => $user->email, 'password' => 'keliru'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->inactive()->create(['password' => Hash::make('rahasia')]);

        $this->post('/login', ['login' => $user->email, 'password' => 'rahasia'])
            ->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_password_recovery_form_can_be_opened(): void
    {
        $this->get('/forgot-password')->assertOk();
    }

    public function test_change_password_requires_authentication(): void
    {
        $this->get('/password/change')->assertRedirect('/login');
        $this->put('/password/change', [])->assertRedirect('/login');
    }
}
