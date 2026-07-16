<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Models\Finance\FeeType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeeTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_bendahara_can_create_fee_type(): void
    {
        $this->seed();
        $user = User::factory()->create();
        $user->assignRole('bendahara');
        $this->actingAs($user)->post(route('finance.fee-types.store'), [
            'code' => 'UJI-FT', 'name' => 'Ujian Tes', 'category' => 'ujian', 'billing_frequency' => 'semester', 'default_amount' => 10000, 'is_mandatory' => true, 'is_active' => true,
        ])->assertRedirect();
        $this->assertDatabaseHas('fee_types', ['code' => 'UJI-FT']);
    }

    public function test_fee_type_amount_cannot_be_negative(): void
    {
        $this->seed();
        $user = User::factory()->create();
        $user->assignRole('bendahara');
        $this->actingAs($user)->post(route('finance.fee-types.store'), [
            'code' => 'NEG', 'name' => 'Negatif', 'category' => 'lainnya', 'billing_frequency' => 'sekali', 'default_amount' => -1,
        ])->assertSessionHasErrors('default_amount');
    }
}
