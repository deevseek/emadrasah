<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Student, User};
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RfidWriterPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
    }

    public function test_writer_request_uses_a_relative_url_behind_an_https_proxy(): void
    {
        config(['app.url' => 'http://internal-app.test']);

        $operator = User::factory()->create(['must_change_password' => false]);
        $operator->assignRole('operator');
        $student = Student::create([
            'full_name' => 'Ahmad Fauzan',
            'gender' => 'male',
            'status' => 'active',
        ]);

        $response = $this->actingAs($operator)->get("https://madrasah.test/students/{$student->id}");

        $response->assertOk()
            ->assertSee("const writerUrl = \"/students/{$student->id}/rfid-writer\";", false)
            ->assertDontSee('http://internal-app.test/students', false);
    }
}
