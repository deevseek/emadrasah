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
 public function test_closed_registration_cannot_open_online_form(): void { $this->setting(['registration_close_at'=>now()->subMinute()]); $this->get(route('pmbm.public.create'))->assertForbidden(); }
 public function test_status_lookup_requires_birth_date_factor(): void { $this->get(route('pmbm.status.show',['number'=>'PMBM-TEST-0001']))->assertRedirect(); }
}
