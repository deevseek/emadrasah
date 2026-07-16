<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BtaqLevel;
use App\Models\BtaqMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;


class BtaqMasterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_permission_can_manage_level_and_material(): void
    {
        Permission::findOrCreate('btaq-levels.view');
        Permission::findOrCreate('btaq-levels.manage');
        Permission::findOrCreate('btaq-materials.view');
        $user = User::factory()->create();
        $user->givePermissionTo(['btaq-levels.view','btaq-levels.manage','btaq-materials.view']);
        $this->actingAs($user)->post(route('btaq-levels.store'), ['code'=>'IQ1','name'=>'Iqra 1','sequence'=>1,'is_active'=>1])->assertRedirect();
        $level = BtaqLevel::firstOrFail();
        BtaqMaterial::create(['btaq_level_id'=>$level->id,'code'=>'IQ1-M1','name'=>'Halaman 1','category'=>'reading','sequence'=>1,'is_active'=>true]);
        $this->actingAs($user)->get(route('btaq-materials.index'))->assertOk()->assertSee('Materi BTAQ');
        $this->assertDatabaseHas('activity_log', ['event' => 'btaq-level.created']);
    }
}
