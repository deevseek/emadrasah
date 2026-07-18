<?php

declare(strict_types=1);

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Controller;
use App\Models\AssessmentComponent;
use App\Models\Classroom;
use App\Models\ReportCard;
use App\Models\StudentEnrollment;
use App\Models\TeachingAssignment;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Module10Controller extends Controller
{
    public function index(): View { return $this->page('Ikhtisar Penilaian & Rapor', 'assessment-module.index'); }
    public function configuration(): View { return $this->page('Konfigurasi Penilaian', 'assessment-module.configuration'); }
    public function components(): View { return $this->page('Komponen Penilaian', 'assessment-module.components', ['components' => AssessmentComponent::query()->latest()->paginate(15)]); }
    public function minimumCriteria(): View { return $this->page('KKM', 'assessment-module.minimum-criteria'); }
    public function periods(): View { return $this->page('Periode Penilaian', 'assessment-module.periods'); }
    public function myGrades(): View { return $this->page('Nilai Saya', 'assessment-module.my-grades', ['assignments' => TeachingAssignment::query()->with(['classroom','subject'])->where('is_active', true)->paginate(15)]); }
    public function input(Request $request): View { return $this->page('Input Nilai', 'assessment-module.input', ['enrollments' => StudentEnrollment::query()->with('student')->where('enrollment_status', 'active')->paginate(30)]); }
    public function verification(): View { return $this->page('Verifikasi Nilai', 'assessment-module.grade-verification'); }
    public function leger(): View { return $this->page('Leger Nilai', 'assessment-module.leger', ['classes' => Classroom::query()->withCount('studentEnrollments')->paginate(15)]); }
    public function reportClass(): View { return $this->page('Rapor Kelas Saya', 'assessment-module.report-class', ['cards' => ReportCard::query()->with(['student','classroom'])->latest()->paginate(15)]); }
    public function reportDetail(?ReportCard $reportCard = null): View { return $this->page('Detail Rapor', 'assessment-module.report-detail', ['card' => $reportCard]); }
    public function reportVerification(): View { return $this->page('Verifikasi Rapor', 'assessment-module.report-verification', ['cards' => ReportCard::query()->with(['student','classroom'])->where('status', 'submitted')->paginate(15)]); }
    public function reports(): View { return $this->page('Laporan Penilaian', 'assessment-module.reports'); }
    public function printLeger(): View { return view('assessment-module.print-leger', ['classes' => Classroom::query()->withCount('studentEnrollments')->get()]); }
    public function printReport(?ReportCard $reportCard = null): View { return view('assessment-module.print-report', ['card' => $reportCard]); }
    public function exportLeger(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            echo "kelas,jumlah_siswa\n";
            Classroom::query()->withCount('studentEnrollments')->orderBy('name')->each(fn ($classroom) => print($classroom->name.','.$classroom->student_enrollments_count."\n"));
        }, 'leger-nilai.csv', ['Content-Type' => 'text/csv']);
    }
    private function page(string $title, string $view, array $data = []): View { return view($view, ['title' => $title] + $data); }
}
