<?php

declare(strict_types=1);
namespace Tests\Feature;
use App\Models\AcademicYear;
use App\Models\Pmbm\PmbmSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class PmbmPublicFlowTest extends TestCase {
 use RefreshDatabase;
 private function setting(array $attributes=[]): PmbmSetting { $year=AcademicYear::create(['name'=>'2026/2027','starts_at'=>'2026-07-01','ends_at'=>'2027-06-30','is_active'=>true]); return PmbmSetting::create($attributes+['academic_year_id'=>$year->id,'enabled'=>true,'online_registration_enabled'=>true,'registration_open_at'=>now()->subDay(),'registration_close_at'=>now()->addDay(),'fee_items'=>[['name'=>'Pendaftaran','male'=>100000,'female'=>110000]],'schedule_items'=>[['title'=>'Gelombang 1','description'=>'Sesuai konfigurasi panitia']]]); }
 public function test_homepage_links_to_pmbm_and_landing_is_public(): void { $this->setting(); $this->get(route('public.home'))->assertOk()->assertSee(route('pmbm.public.index')); $this->get(route('pmbm.public.index'))->assertOk()->assertSee('Rincian Biaya')->assertSee('Gelombang 1'); }
 public function test_online_form_is_public_only_while_registration_is_open(): void { $this->setting(); $this->get(route('pmbm.public.create'))->assertOk()->assertSee('Identitas Calon Murid')->assertSee('Data Ayah Kandung')->assertSee('Dokumen Persyaratan'); }
 public function test_closed_registration_cannot_open_online_form(): void { $this->setting(['registration_close_at'=>now()->subMinute()]); $this->get(route('pmbm.public.create'))->assertOk()->assertSee('Pendaftaran Online Telah Ditutup')->assertDontSee('data-pmbm-wizard',false); }
 public function test_status_lookup_requires_birth_date_factor(): void { $this->get(route('pmbm.status.show',['number'=>'PMBM-TEST-0001']))->assertRedirect(); }
 public function test_login_pages_link_to_public_student_registration(): void
 {
  foreach (['login', 'parent.login'] as $route) {
   $this->get(route($route))->assertOk()
    ->assertSee('Pendaftaran Siswa Baru Online')
    ->assertSee(route('pmbm.public.index'))
    ->assertSee(route('pmbm.status.form'));
  }
 }
 public function test_invalid_submission_preserves_guardian_and_religion_choices(): void
 {
  $this->setting();
  $this->from(route('pmbm.public.create'))->post(route('pmbm.public.store'), [
   'religion'=>'Katolik', 'guardian_type'=>'other', 'father_citizenship'=>'wna',
  ])->assertRedirect(route('pmbm.public.create'))->assertSessionHasErrors('full_name');
  $this->get(route('pmbm.public.create'))->assertOk()
   ->assertSee('value="other" selected', false)
   ->assertSee('<option selected>Katolik</option>', false)
   ->assertSee('value="wna" selected', false);
 }
 public function test_upcoming_registration_keeps_public_button_and_form_preview(): void
 {
  $this->setting(['registration_open_at'=>now()->addDay(),'registration_close_at'=>now()->addMonth()]);
  $this->get(route('public.home'))->assertOk()->assertSee('Daftar Online Siswa Baru')->assertSee(route('pmbm.public.create'));
  $this->get(route('pmbm.public.index'))->assertOk()->assertSee('Daftar Online Siswa Baru')->assertSee(route('pmbm.public.create'));
  $this->get(route('pmbm.public.create'))->assertOk()->assertSee('Identitas Calon Murid')->assertSee('Pengiriman tersedia setelah pendaftaran dibuka')->assertSee('data-wizard-submit disabled', false);
 }
 public function test_upcoming_registration_rejects_submission_in_service(): void
 {
  $setting=$this->setting(['registration_open_at'=>now()->addDay(),'registration_close_at'=>now()->addMonth()]);
  $this->expectException(\Illuminate\Validation\ValidationException::class);
  app(\App\Services\Pmbm\PmbmRegistrationService::class)->submit(['academic_year_id'=>$setting->academic_year_id], 'online');
 }
 public function test_public_requirements_are_scoped_to_active_year_and_online_channel(): void
 {
  $setting=$this->setting(['admission_requirements'=>"Usia minimal 6 tahun.\n<script>alert(1)</script>",'registration_instructions'=>'Hubungi panitia untuk bantuan.']);
  foreach ([['Akta Kelahiran Publik','both',true],['Berkas Offline Saja','offline',true],['Berkas Nonaktif','online',false]] as $i=>[$name,$channel,$active]) {
   $setting->documentRequirements()->create(['name'=>$name,'code'=>'berkas_'.$i,'channel'=>$channel,'is_active'=>$active,'is_required'=>true,'sort_order'=>$i]);
  }
  $this->get(route('pmbm.public.index'))->assertOk()->assertSee('Usia minimal 6 tahun.')
   ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;',false)->assertDontSee('<script>alert(1)</script>',false)
   ->assertSee('Akta Kelahiran Publik')->assertSee('Wajib')->assertSee('Hubungi panitia untuk bantuan.')
   ->assertDontSee('Berkas Offline Saja')->assertDontSee('Berkas Nonaktif');
 }
 public function test_panitia_can_save_public_admission_requirements(): void
 {
  $setting=$this->setting();
  $permission=\Spatie\Permission\Models\Permission::findOrCreate('pmbm.settings.update','web');
  $user=\App\Models\User::factory()->create();
  $user->givePermissionTo($permission);
  $this->actingAs($user)->get(route('pmbm.settings.edit',$setting))->assertOk()->assertSee('Persyaratan Pendaftaran Siswa Baru');
  $this->actingAs($user)->put(route('pmbm.settings.update',$setting),[
   'academic_year_id'=>$setting->academic_year_id,'title'=>'Penerimaan Murid Baru','registration_prefix'=>'PMBM',
   'enabled'=>true,'online_registration_enabled'=>true,'minimum_initial_payment_percent'=>50,
   'allowed_payment_methods'=>['virtual_account'],'admission_requirements'=>'Usia minimal 6 tahun.',
  ])->assertSessionHasNoErrors()->assertRedirect();
  $this->assertDatabaseHas('pmbm_settings',['id'=>$setting->id,'admission_requirements'=>'Usia minimal 6 tahun.']);
  $this->get(route('pmbm.public.index'))->assertOk()->assertSee('Usia minimal 6 tahun.');
 }
}
