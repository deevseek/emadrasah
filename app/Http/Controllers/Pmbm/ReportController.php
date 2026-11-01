<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pmbm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pmbm\ReportFilterRequest;
use App\Models\AcademicYear;
use App\Services\Foundation\SchoolProfileService;
use App\Services\Pmbm\PmbmReportService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private function data(ReportFilterRequest $request, PmbmReportService $reports): array
    {
        $filters = array_filter($request->validated(), fn ($value) => $value !== null && $value !== '');
        $years = AcademicYear::latest('starts_at')->get();

        return ['filters' => $filters, 'years' => $years, 'summary' => $reports->summary($filters), 'selectedYear' => $years->firstWhere('id', $filters['academic_year_id'] ?? null)];
    }

    public function index(ReportFilterRequest $request, PmbmReportService $reports): View
    {
        $data = $this->data($request, $reports);

        return view('pmbm.admin.report', [...$data, 'applicants' => $reports->ordered($data['filters'])->paginate(30)->withQueryString()]);
    }

    public function print(ReportFilterRequest $request, PmbmReportService $reports, SchoolProfileService $profiles): View
    {
        $data = $this->data($request, $reports);

        return view('pmbm.admin.report-print', [...$data, 'applicants' => $reports->ordered($data['filters'])->lazy(500), 'school' => $profiles->current(), 'printedAt' => now(), 'officer' => $request->user()->name]);
    }

    public function export(ReportFilterRequest $request, PmbmReportService $reports): StreamedResponse
    {
        $filters = $request->validated();

        return response()->streamDownload(function () use ($filters, $reports): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Nomor Pendaftaran', 'Nama Lengkap', 'Tahun Pelajaran', 'Tanggal Pendaftaran', 'Sumber', 'Status', 'Jenis Kelamin'], ';', '"', '');
            foreach ($reports->ordered($filters)->lazy(500) as $a) {
                $cells = [$a->registration_number, $a->full_name, $a->academicYear?->name, $a->submitted_at?->format('d/m/Y'), ucfirst($a->registration_source), $a->status->label(), $a->gender === 'male' ? 'Laki-laki' : ($a->gender === 'female' ? 'Perempuan' : '—')];
                fputcsv($out, array_map($reports->csvCell(...), $cells), ';', '"', '');
            }
            fclose($out);
        }, 'laporan-spmb-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
