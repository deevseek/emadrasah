<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\ImportPreviewRequest;
use App\Http\Requests\Academic\ImportProcessRequest;
use App\Models\AcademicYear;
use App\Models\ImportBatch;
use App\Services\Academic\Imports\LessonScheduleImportService;
use App\Services\Academic\Imports\SimpleXlsx;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ScheduleImportController extends Controller
{
    private const HEADERS = ['tahun_ajaran', 'semester', 'kelas', 'kode_kelas', 'hari', 'waktu_mulai', 'waktu_selesai', 'jenis_slot', 'mata_pelajaran', 'kode_mata_pelajaran', 'nama_kegiatan', 'guru', 'nomor_pegawai', 'jp', 'ruangan', 'keterangan', 'kode_sesi_bersama', 'nama_sesi_bersama'];

    public function index(): View
    {
        return view('imports.form', ['kind' => 'schedule', 'title' => 'Impor Jadwal Pelajaran', 'academicYears' => AcademicYear::with('semesters')->get(), 'batches' => ImportBatch::with('importer')->where('type', 'lesson_schedule')->latest()->take(20)->get()]);
    }

    public function template(SimpleXlsx $xlsx)
    {
        return $xlsx->download(self::HEADERS, [['2026/2027', 'Ganjil', 'I Ar-Rahman', '', 'senin', '06:50', '07:25', 'lesson', 'Matematika', '', '', 'Nama Guru', '', '1', 'I Ar-Rahman', ''], ['2026/2027', 'Ganjil', 'I Ar-Rahman', '', 'senin', '09:00', '09:15', 'break', '', '', 'Istirahat', '', '', '1', '', '']], 'template-jadwal-pelajaran.xlsx');
    }

    public function expiredPreview(): RedirectResponse
    {
        return redirect()->route('schedules.import')->withErrors(['preview' => 'Sesi preview telah berakhir. Silakan unggah kembali berkas.']);
    }

    public function preview(ImportPreviewRequest $request, LessonScheduleImportService $service): View
    {
        $file = $request->file('file');
        $path = $file->store('academic-imports', 'local');
        $preview = $service->preview(storage_path('app/private/'.$path), (int) $request->academic_year_id, (int) $request->semester_id);
        $token = (string) str()->uuid();
        session()->put('import_preview.'.$token, $preview + ['year' => (int) $request->academic_year_id, 'semester' => (int) $request->semester_id, 'filename' => $file->getClientOriginalName()]);

        return view('imports.preview', ['kind' => 'schedule', 'title' => 'Periksa Data Jadwal Pelajaran', 'preview' => $preview, 'token' => $token]);
    }

    public function process(ImportProcessRequest $request, LessonScheduleImportService $service): RedirectResponse
    {
        $key = 'import_preview.'.$request->preview_token;
        $data = session()->get($key);

        if (! $data) {
            return redirect()->route('schedules.import')->withErrors(['preview' => 'Sesi preview telah berakhir. Silakan unggah kembali berkas.']);
        }

        try {
            $batch = $service->process($data['rows'], $data['year'], $data['semester'], $data['filename']);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['import' => 'Jadwal belum berhasil diimpor. Periksa data lalu coba kembali.']);
        }

        session()->forget($key);

        return redirect()->route('schedules.import')->with('status', "Impor selesai: {$batch->imported_rows} slot disimpan.");
    }
}
