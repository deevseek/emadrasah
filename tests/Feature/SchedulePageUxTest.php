<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SchoolSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class SchedulePageUxTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_page_uses_clear_indonesian_operator_language(): void
    {
        Storage::fake('local');
        $this->seed();
        $path = 'private/academic/templates/lesson-schedules/official-schedule.docx';
        Storage::disk('local')->put($path, 'fixture');
        SchoolSetting::query()->updateOrCreate(
            ['group' => 'lesson_schedule_templates', 'key' => 'official_docx_path'],
            ['value' => $path, 'type' => 'file', 'is_public' => false],
        );

        $response = $this->actingAs(User::query()->where('email', 'admin@example.test')->firstOrFail())
            ->get(route('schedules.index'));

        $response->assertOk()
            ->assertSeeText('Jadwal Pelajaran')
            ->assertSeeText('Kelola jadwal pelajaran setiap kelas dan guru.')
            ->assertSeeText('Template Jadwal')
            ->assertSeeText('Unggah Template Baru')
            ->assertSeeText('Unduh Template Saat Ini')
            ->assertSeeText('Unduh Jadwal Word')
            ->assertSeeText('Cari Jadwal')
            ->assertSeeText('Jenis Kegiatan')
            ->assertSeeText('Jadwal Mingguan')
            ->assertDontSeeText('Export Word Resmi')
            ->assertDontSeeText('Jenis slot')
            ->assertDontSeeText('Ringkasan pemetaan')
            ->assertDontSeeText('sel tidak terpetakan')
            ->assertDontSeeText('disimpan secara privat')
            ->assertDontSeeText('Jadwal PelajaranJadwal Per Kelas');
    }
}
