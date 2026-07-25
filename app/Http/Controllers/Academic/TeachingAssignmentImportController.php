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
use App\Services\Academic\Imports\SimpleXlsx;
use App\Services\Academic\Imports\TeachingAssignmentImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class TeachingAssignmentImportController extends Controller
{
    private const TYPE = 'teaching_assignment';
    private const HEADERS = ['tahun_ajaran', 'semester', 'nama_guru', 'nomor_pegawai', 'kelas', 'kode_kelas', 'mata_pelajaran', 'kode_mata_pelajaran', 'jp_per_minggu', 'tanggal_mulai', 'tanggal_selesai', 'keterangan', 'aktif'];

    public function index(): View { return view('imports.form', ['kind' => 'teaching', 'title' => 'Impor Penugasan Mengajar', 'academicYears' => AcademicYear::with('semesters')->get(), 'batches' => ImportBatch::with('importer')->where('type', self::TYPE)->latest()->take(20)->get()]); }
    public function template(SimpleXlsx $xlsx) { return $xlsx->download(self::HEADERS, [['2026/2027', 'Ganjil', 'Nama Guru', 'PEG-001', 'I As-Salam', 'I-AS', 'Matematika', 'MTK', '4', '2026-07-01', '2026-12-31', '', '1']], 'template-penugasan-mengajar.xlsx'); }
    public function expiredPreview(): RedirectResponse { return redirect()->route('teaching-assignments.import')->withErrors(['preview' => 'Data preview tidak ditemukan. Silakan unggah kembali berkas.']); }

    public function preview(ImportPreviewRequest $request, TeachingAssignmentImportService $service, ImportPreviewStore $store): View
    {
        $file = $request->file('file'); $path = $file->store('academic-imports', 'local');
        try { $preview = $service->preview(storage_path('app/private/'.$path), (int) $request->academic_year_id, (int) $request->semester_id); } finally { Storage::disk('local')->delete($path); }
        $stored = $store->create(self::TYPE, (int) $request->user()->id, (int) $request->academic_year_id, (int) $request->semester_id, $file->getClientOriginalName(), $preview + ['year' => (int) $request->academic_year_id, 'semester' => (int) $request->semester_id, 'mode' => $request->mode ?? 'create', 'filename' => $file->getClientOriginalName()]);
        return $this->previewView($preview, $stored->token);
    }

    public function show(string $token, ImportPreviewStore $store): View
    {
        try { $data = $store->read($token, self::TYPE, (int) request()->user()->id); }
        catch (ImportPreviewException $e) { if (in_array($e->reason, ['forbidden', 'wrong_type'], true)) abort(403); abort($e->reason === 'expired' ? 410 : 404, $e->getMessage()); }
        return $this->previewView($data, $token);
    }

    public function process(ImportProcessRequest $request, TeachingAssignmentImportService $service, ImportPreviewStore $store): RedirectResponse
    {
        try {
            $batch = DB::transaction(function () use ($request, $service, $store) {
                $preview = $store->locate($request->preview_token, self::TYPE, (int) $request->user()->id, true); $data = $store->readPreview($preview);
                if (($data['mode'] ?? 'create') === 'replace' && ! $request->boolean('confirm_replace')) throw ValidationException::withMessages(['confirm_replace' => 'Konfirmasi penonaktifan wajib diberikan.']);
                $batch = $service->process($data['rows'], $data['year'], $data['semester'], $data['mode'] ?? 'create', $data['filename']); $store->consume($preview); return $batch;
            });
        } catch (ImportPreviewException $e) {
            if (in_array($e->reason, ['forbidden', 'wrong_type'], true)) abort(403);
            $message = $e->reason === 'consumed' ? 'Penugasan dari preview ini sudah diproses.' : ($e->reason === 'expired' ? 'Preview telah kedaluwarsa. Silakan unggah kembali berkas.' : 'Data preview tidak ditemukan. Silakan unggah kembali berkas.');
            return redirect()->route('teaching-assignments.import')->withErrors(['preview' => $message]);
        } catch (ValidationException $e) { return redirect()->route('teaching-assignments.import.preview.show', $request->preview_token)->withErrors($e->errors()); }
        catch (Throwable $e) { report($e); return redirect()->route('teaching-assignments.import.preview.show', $request->preview_token)->withErrors(['import' => 'Penugasan belum berhasil disimpan. Data preview masih tersedia dan dapat diproses kembali.']); }
        return redirect()->route('teaching-assignments.import')->with('status', "Impor selesai: {$batch->imported_rows} data disimpan.");
    }

    private function previewView(array $preview, string $token): View { return view('imports.preview', ['kind' => 'teaching', 'title' => 'Preview Penugasan Mengajar', 'preview' => $preview, 'token' => $token]); }
}
