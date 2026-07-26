<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academic;

use App\Exceptions\ImportPreviewException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\ImportPreviewRequest;
use App\Http\Requests\Academic\ImportProcessRequest;
use App\Models\AcademicYear;
use App\Models\ImportBatch;
use App\Services\Academic\Imports\ImportPreviewStore;
use App\Services\Academic\Imports\LessonScheduleImportService;
use App\Services\Academic\Imports\SimpleXlsx;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ScheduleImportController extends Controller
{
    private const HEADERS = ['tahun_ajaran', 'semester', 'kelas', 'kode_kelas', 'hari', 'waktu_mulai', 'waktu_selesai', 'jenis_slot', 'mata_pelajaran', 'kode_mata_pelajaran', 'nama_kegiatan', 'guru', 'nomor_pegawai', 'jp', 'ruangan', 'keterangan', 'kode_sesi_bersama', 'nama_sesi_bersama'];
    private const TYPE = 'lesson_schedule';

    public function index(): View
    {
        return view('imports.form', ['kind' => 'schedule', 'title' => 'Impor Jadwal Pelajaran', 'academicYears' => AcademicYear::with('semesters')->get(), 'batches' => ImportBatch::with('importer')->where('type', self::TYPE)->latest()->take(20)->get()]);
    }

    public function template(SimpleXlsx $xlsx)
    {
        return $xlsx->download(self::HEADERS, [['2026/2027', 'Ganjil', 'I Ar-Rahman', '', 'senin', '06:50', '07:25', 'lesson', 'Matematika', '', '', 'Nama Guru', '', '1', 'I Ar-Rahman', '']], 'template-jadwal-pelajaran.xlsx');
    }

    public function expiredPreview(): RedirectResponse
    {
        return redirect()->route('schedules.import')->withErrors(['preview' => 'Data preview tidak ditemukan. Silakan periksa kembali file jadwal.']);
    }

    public function preview(ImportPreviewRequest $request, LessonScheduleImportService $service, ImportPreviewStore $store): RedirectResponse
    {
        $file = $request->file('file');
        $path = $file->store('academic-imports', 'local');
        try {
            $preview = $service->preview(storage_path('app/private/'.$path), (int) $request->academic_year_id, (int) $request->semester_id);
        } finally {
            \Storage::disk('local')->delete($path);
        }
        $stored = $store->create(self::TYPE, (int) $request->user()->id, (int) $request->academic_year_id, (int) $request->semester_id, $file->getClientOriginalName(), $preview + ['year' => (int) $request->academic_year_id, 'semester' => (int) $request->semester_id, 'filename' => $file->getClientOriginalName()]);

        return redirect()->route('schedules.import.preview.show', $stored->token);
    }

    public function show(string $token, ImportPreviewStore $store): View
    {
        try {
            $data = $store->read($token, self::TYPE, (int) request()->user()->id);
        } catch (ImportPreviewException $exception) {
            if (in_array($exception->reason, ['forbidden', 'wrong_type'], true)) {
                abort(403);
            }
            abort($exception->reason === 'expired' ? 410 : 404, $exception->getMessage());
        }

        return $this->previewView($data, $token);
    }

    public function process(ImportProcessRequest $request, LessonScheduleImportService $service, ImportPreviewStore $store): RedirectResponse
    {
        try {
            $batch = DB::transaction(function () use ($request, $service, $store) {
                $preview = $store->locate($request->preview_token, self::TYPE, (int) $request->user()->id, true);
                $data = $store->readPreview($preview);
                $batch = $service->process($data['rows'], $data['year'], $data['semester'], $data['filename']);
                $store->consume($preview);

                return $batch;
            });
        } catch (ImportPreviewException $exception) {
            return $this->previewFailure($exception);
        } catch (ValidationException $exception) {
            return redirect()->route('schedules.import.preview.show', $request->preview_token)->withErrors($exception->errors())->withErrors(['import' => 'Jadwal belum berhasil disimpan. Data preview masih tersedia dan dapat diproses kembali.']);
        } catch (Throwable $exception) {
            report($exception);
            return redirect()->route('schedules.import.preview.show', $request->preview_token)->withErrors(['import' => 'Jadwal belum berhasil disimpan. Data preview masih tersedia dan dapat diproses kembali.']);
        }

        return redirect()->route('schedules.import')->with('status', "Impor selesai: {$batch->imported_rows} slot disimpan.");
    }

    private function previewView(array $preview, string $token): View
    {
        return view('imports.preview', ['kind' => 'schedule', 'title' => 'Periksa Data Jadwal Pelajaran', 'preview' => $preview, 'token' => $token]);
    }

    private function previewFailure(ImportPreviewException $exception): RedirectResponse
    {
        if (in_array($exception->reason, ['forbidden', 'wrong_type'], true)) abort(403);
        $message = match ($exception->reason) {
            'expired' => 'Preview telah kedaluwarsa. Silakan unggah kembali file jadwal.',
            'consumed' => 'Jadwal dari preview ini sudah diproses.',
            default => 'Data preview tidak ditemukan. Silakan periksa kembali file jadwal.',
        };
        return redirect()->route('schedules.import')->withErrors(['preview' => $message]);
    }
}
