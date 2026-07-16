<?php

declare(strict_types=1);

namespace App\Http\Controllers\StudentAffairs;

use App\Enums\AdmissionType;
use App\Enums\Gender;
use App\Enums\GuardianRelationship;
use App\Enums\StudentDocumentType;
use App\Enums\StudentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StudentAffairs\GuardianStudentRequest;
use App\Http\Requests\StudentAffairs\StatusChangeRequest;
use App\Http\Requests\StudentAffairs\StudentDocumentRequest;
use App\Http\Requests\StudentAffairs\StudentRequest;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\User;
use App\Services\StudentAffairs\GuardianAssignmentService;
use App\Services\StudentAffairs\StudentDocumentService;
use App\Services\StudentAffairs\StudentService;
use App\Services\StudentAffairs\StudentStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Student::with(['activeEnrollment.classroom', 'guardians'])
            ->when($request->search, fn ($query, $search) => $query->where(fn ($where) => $where
                ->where('name', 'like', "%{$search}%")
                ->orWhere('student_number', 'like', "%{$search}%")
                ->orWhere('national_student_number', 'like', "%{$search}%")
                ->orWhere('national_identity_number', 'like', "%{$search}%")
                ->orWhere('family_card_number', 'like', "%{$search}%")
                ->orWhereHas('guardians', fn ($guardian) => $guardian->where('name', 'like', "%{$search}%")->orWhere('whatsapp', 'like', "%{$search}%"))))
            ->when($request->status, fn ($query, $status) => $query->where('student_status', $status))
            ->when($request->gender, fn ($query, $gender) => $query->where('gender', $gender))
            ->when(! $request->filled('status'), fn ($query) => $query->where('student_status', StudentStatus::Active->value))
            ->when($request->year_in, fn ($query, $year) => $query->whereYear('admission_date', $year))
            ->when($request->academic_year_id, fn ($query, $academicYearId) => $query->whereHas('enrollments', fn ($enrollment) => $enrollment
                ->where('academic_year_id', $academicYearId)
                ->where('enrollment_status', 'active')))
            ->when($request->classroom_id, fn ($query, $classroomId) => $query->whereHas('enrollments', fn ($enrollment) => $enrollment
                ->where('classroom_id', $classroomId)
                ->where('enrollment_status', 'active')));

        return view('student-affairs.students.index', [
            'students' => $query->latest()->paginate(15)->withQueryString(),
            'totalStudents' => (clone $query)->count(),
            'statuses' => StudentStatus::cases(),
            'genders' => Gender::cases(),
            'academicYears' => AcademicYear::all(),
            'classrooms' => Classroom::with('academicYear')->get(),
        ]);
    }

    public function create(): View
    {
        return view('student-affairs.students.form', $this->refs() + ['student' => new Student]);
    }

    public function store(StudentRequest $request, StudentService $service): RedirectResponse
    {
        $student = $service->save($request->validated(), null, $request->file('photo'));

        return redirect()->route('students.show', $student)->with('status', 'Data siswa berhasil ditambahkan.');
    }

    public function show(Student $student): View
    {
        $student->load(['user', 'guardians', 'enrollments.classroom.academicYear', 'statusHistories.changedBy', 'documents.uploader']);

        return view('student-affairs.students.show', [
            'student' => $student,
            'guardians' => Guardian::where('is_active', true)->get(),
            'relationships' => GuardianRelationship::cases(),
            'documentTypes' => StudentDocumentType::cases(),
            'statuses' => StudentStatus::cases(),
            'activities' => Activity::query()->where('subject_type', Student::class)->where('subject_id', $student->id)->latest()->limit(10)->get(),
        ]);
    }

    public function edit(Student $student): View
    {
        return view('student-affairs.students.form', $this->refs() + ['student' => $student]);
    }

    public function update(StudentRequest $request, Student $student, StudentService $service): RedirectResponse
    {
        $service->save($request->validated(), $student, $request->file('photo'));

        return redirect()->route('students.show', $student)->with('status', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Student $student, StudentService $service): RedirectResponse
    {
        $service->delete($student);

        return redirect()->route('students.index')->with('status', 'Data siswa dinonaktifkan.');
    }

    public function attachGuardian(GuardianStudentRequest $request, Student $student, GuardianAssignmentService $service): RedirectResponse
    {
        $service->attach($student, $request->validated());

        return back()->with('status', 'Wali siswa berhasil ditautkan.');
    }

    public function updateGuardian(GuardianStudentRequest $request, Student $student, Guardian $guardian, GuardianAssignmentService $service): RedirectResponse
    {
        $service->update($student, $guardian->id, collect($request->validated())->except('guardian_id')->all());

        return back()->with('status', 'Relasi wali siswa berhasil diperbarui.');
    }

    public function detachGuardian(Student $student, Guardian $guardian, GuardianAssignmentService $service): RedirectResponse
    {
        $service->detach($student, $guardian->id);

        return back()->with('status', 'Relasi wali siswa berhasil dihapus.');
    }

    public function changeStatus(StatusChangeRequest $request, Student $student, StudentStatusService $service): RedirectResponse
    {
        $service->change($student, $request->validated());

        return back()->with('status', 'Status siswa berhasil diubah.');
    }

    public function uploadDocument(StudentDocumentRequest $request, Student $student, StudentDocumentService $service): RedirectResponse
    {
        $service->upload($student, $request->validated(), $request->file('file'));

        return back()->with('status', 'Dokumen siswa berhasil diunggah.');
    }

    public function downloadDocument(StudentDocument $document): StreamedResponse
    {
        abort_unless(request()->user()?->can('students.manage-documents'), 403);

        return Storage::disk('local')->download($document->file_path, $document->document_type->label().'.'.pathinfo($document->file_path, PATHINFO_EXTENSION));
    }

    public function deleteDocument(StudentDocument $document, StudentDocumentService $service): RedirectResponse
    {
        $service->delete($document);

        return back()->with('status', 'Dokumen siswa berhasil dihapus.');
    }


    public function export(Request $request): StreamedResponse
    {
        $fileName = 'data-siswa-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($request): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Nama', 'NIS', 'NISN', 'Jenis Kelamin', 'Status', 'Tanggal Masuk', 'Kontak Utama', 'WhatsApp Wali']);
            Student::with('guardians')
                ->when(! $request->filled('status'), fn ($query) => $query->where('student_status', StudentStatus::Active->value))
                ->when($request->status, fn ($query, $status) => $query->where('student_status', $status))
                ->when($request->gender, fn ($query, $gender) => $query->where('gender', $gender))
                ->when($request->search, fn ($query, $search) => $query->where(fn ($where) => $where->where('name', 'like', "%{$search}%")->orWhere('student_number', 'like', "%{$search}%")->orWhere('national_student_number', 'like', "%{$search}%")))
                ->orderBy('name')
                ->chunk(200, function ($students) use ($handle): void {
                    foreach ($students as $student) {
                        $primary = $student->guardians->first(fn ($guardian) => (bool) $guardian->pivot->is_primary) ?? $student->guardians->first();
                        fputcsv($handle, [$student->name, $student->student_number, $student->national_student_number, $student->gender?->label(), $student->student_status?->label(), $student->admission_date?->format('Y-m-d'), $primary?->name, $primary?->whatsapp]);
                    }
                });
            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    private function refs(): array
    {
        return [
            'genders' => Gender::cases(),
            'admissionTypes' => AdmissionType::cases(),
            'statuses' => StudentStatus::cases(),
            'users' => User::whereDoesntHave('student')->get(),
        ];
    }
}
