<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\GradeLevel;
use App\Models\LessonSchedule;
use App\Models\SchoolSetting;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

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
            ->assertSeeText('Gunakan menu ini untuk membuat jadwal sesuai template Word madrasah.')
            ->assertSeeText('Cari Jadwal')
            ->assertSeeText('Jenis Kegiatan')
            ->assertSeeText('Jadwal Mingguan')
            ->assertDontSeeText('Export Word Resmi')
            ->assertDontSeeText('Jenis slot')
            ->assertDontSeeText('Ringkasan pemetaan')
            ->assertDontSeeText('sel tidak terpetakan')
            ->assertDontSeeText('disimpan secara privat')
            ->assertDontSeeText('Jadwal PelajaranJadwal Per Kelas');

        $response->assertSee('action="'.route('schedules.export-word').'"', false)
            ->assertSee('name="tahun_ajaran_id"', false)
            ->assertSee('name="semester_id"', false)
            ->assertSee('name="tanggal_dokumen"', false)
            ->assertSee('name="kelas"', false)
            ->assertDontSee('href="'.route('schedules.print').'"', false)
            ->assertDontSee('action="'.route('schedules.print').'"', false)
            ->assertDontSeeText('Cetak Daftar Ringkas');
    }

    public function test_compact_print_applies_filters_and_explains_an_empty_result(): void
    {
        $this->seed();

        $this->actingAs(User::query()->where('email', 'admin@example.test')->firstOrFail())
            ->get(route('schedules.print', [
                'academic_year_id' => 999999,
                'semester_id' => 999999,
                'classroom_id' => 999999,
                'employee_id' => 999999,
                'day_of_week' => 'senin',
                'entry_type' => 'lesson',
            ]))
            ->assertOk()
            ->assertSeeText('Cetak Daftar Ringkas')
            ->assertSeeText('Menampilkan daftar jadwal dalam bentuk tabel sederhana.')
            ->assertSeeText('Belum ada jadwal untuk pilihan tersebut.');
    }

    public function test_word_download_uses_stored_template_without_changing_it(): void
    {
        Storage::fake('local');
        $this->seed();
        $year = AcademicYear::query()->firstOrFail();
        $semester = Semester::query()->where('academic_year_id', $year->id)->firstOrFail();
        $grade = GradeLevel::query()->firstOrCreate(
            ['code' => 'K1'],
            ['name' => 'Kelas I', 'level' => 1, 'is_active' => true],
        );
        $classroom = Classroom::query()->create([
            'academic_year_id' => $year->id,
            'grade_level_id' => $grade->id,
            'name' => 'I As-Salam (Fullday)',
            'code' => 'I-AS-SALAM',
            'is_active' => true,
        ]);
        LessonSchedule::query()->create([
            'academic_year_id' => $year->id,
            'semester_id' => $semester->id,
            'classroom_id' => $classroom->id,
            'entry_type' => 'activity',
            'activity_name' => 'TASMI’',
            'day_of_week' => 'senin',
            'starts_at' => '07:00',
            'ends_at' => '07:30',
            'is_active' => true,
        ]);

        $path = 'private/academic/templates/lesson-schedules/official-schedule.docx';
        Storage::disk('local')->makeDirectory(dirname($path));
        $zip = new ZipArchive;
        $zip->open(Storage::disk('local')->path($path), ZipArchive::CREATE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="xml" ContentType="application/xml"/></Types>');
        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>${nama_kelas}</w:t></w:r></w:p><w:tbl><w:tr><w:tc><w:p><w:r><w:t>JAM KE</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>WAKTU</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>SENIN</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>SELASA</w:t></w:r></w:p></w:tc></w:tr><w:tr><w:tc><w:p><w:r><w:t>1</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>07.00-07.30</w:t></w:r></w:p></w:tc><w:tc><w:p/></w:tc><w:tc><w:p/></w:tc></w:tr></w:tbl></w:body></w:document>');
        $zip->close();
        SchoolSetting::query()->updateOrCreate(
            ['group' => 'lesson_schedule_templates', 'key' => 'official_docx_path'],
            ['value' => $path, 'type' => 'file', 'is_public' => false],
        );
        $originalHash = hash_file('sha256', Storage::disk('local')->path($path));

        $response = $this->actingAs(User::query()->where('email', 'admin@example.test')->firstOrFail())
            ->get(route('schedules.export-word', [
                'tahun_ajaran_id' => $year->id,
                'semester_id' => $semester->id,
                'tanggal_dokumen' => '2026-07-25',
                'kelas' => $classroom->code,
            ]));

        $response->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $rendered = new ZipArchive;
        $rendered->open($response->baseResponse->getFile()->getPathname());
        $renderedXml = $rendered->getFromName('word/document.xml');
        $rendered->close();
        $this->assertIsString($renderedXml);
        $this->assertStringContainsString('TASMI’', $renderedXml);
        $this->assertSame($originalHash, hash_file('sha256', Storage::disk('local')->path($path)));
    }
}
