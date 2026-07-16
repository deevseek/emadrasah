<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SchoolSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class FoundationCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@example.test')->firstOrFail();
    }

    #[DataProvider('foundationFormRoutes')]
    public function test_foundation_forms_render(string $routeName, string $actionRouteName, string $field, ?string $recordKey = null): void
    {
        $record = match ($recordKey) {
            'user' => $this->admin,
            'setting' => SchoolSetting::query()->firstOrFail(),
            default => null,
        };
        $response = $this->actingAs($this->admin)->get($recordKey === 'user' ? route($routeName, $record) : route($routeName));

        $response->assertOk()
            ->assertSee('<form', false)
            ->assertSee($record ? route($actionRouteName, $record) : route($actionRouteName), false)
            ->assertSee($field);
    }

    public static function foundationFormRoutes(): array
    {
        return [
            'school profile edit' => ['school-profile.edit', 'school-profile.update', 'school_name'],
            'settings index' => ['settings.index', 'settings.update', 'value', 'setting'],
            'users create' => ['users.create', 'users.store', 'name'],
            'users edit' => ['users.edit', 'users.update', 'email', 'user'],
        ];
    }
}
