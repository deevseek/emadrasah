<?php

declare(strict_types=1);

namespace App\Services\StudentAffairs;

use App\Enums\EnrollmentStatus;
use App\Enums\Gender;
use App\Enums\StudentStatus;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use ZipArchive;

class StudentRecapWorkbookService
{
    public function stream(Request $request): string
    {
        $academicYear = $this->academicYear($request);
        $classrooms = $this->classrooms($request, $academicYear);
        $students = $this->students($request);
        $sheets = [];

        foreach ($classrooms as $classroom) {
            $enrollments = $classroom->studentEnrollments()
                ->with('student')
                ->where('enrollment_status', EnrollmentStatus::Active)
                ->whereHas('student', fn ($query) => $this->applyStudentFilters($query, $request))
                ->orderBy(Student::select('name')->whereColumn('students.id', 'student_enrollments.student_id'))
                ->get();

            if ($request->filled('classroom_id') || $enrollments->isNotEmpty()) {
                $sheets[] = [
                    'name' => $this->sheetName($classroom->code ?: $classroom->name),
                    'xml' => $this->classroomSheet($classroom, $academicYear, $enrollments),
                ];
            }
        }

        $sheets[] = [
            'name' => 'REKAP',
            'xml' => $this->recapSheet($students, $classrooms, $academicYear),
        ];

        return $this->writeWorkbook($sheets);
    }

    private function academicYear(Request $request): ?AcademicYear
    {
        if ($request->filled('academic_year_id')) {
            return AcademicYear::find($request->integer('academic_year_id'));
        }

        return AcademicYear::query()->where('is_active', true)->latest('starts_on')->first()
            ?? AcademicYear::query()->latest('starts_on')->first();
    }

    private function classrooms(Request $request, ?AcademicYear $academicYear): Collection
    {
        return Classroom::query()
            ->with(['academicYear', 'gradeLevel', 'homeroomTeacher'])
            ->when($academicYear, fn ($query) => $query->where('academic_year_id', $academicYear->id))
            ->when($request->classroom_id, fn ($query, $id) => $query->whereKey($id))
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
    }

    private function students(Request $request): Collection
    {
        return Student::query()
            ->with(['activeEnrollment.classroom', 'guardians'])
            ->when($request->academic_year_id, fn ($query, $id) => $query->whereHas('enrollments', fn ($enrollment) => $enrollment->where('academic_year_id', $id)->where('enrollment_status', EnrollmentStatus::Active)))
            ->when($request->classroom_id, fn ($query, $id) => $query->whereHas('enrollments', fn ($enrollment) => $enrollment->where('classroom_id', $id)->where('enrollment_status', EnrollmentStatus::Active)))
            ->tap(fn ($query) => $this->applyStudentFilters($query, $request))
            ->orderBy('name')
            ->get();
    }

    private function applyStudentFilters($query, Request $request): void
    {
        $query
            ->when(! $request->filled('status'), fn ($query) => $query->where('student_status', StudentStatus::Active->value))
            ->when($request->status, fn ($query, $status) => $query->where('student_status', $status))
            ->when($request->gender, fn ($query, $gender) => $query->where('gender', $gender))
            ->when($request->search, fn ($query, $search) => $query->where(fn ($where) => $where
                ->where('name', 'like', "%{$search}%")
                ->orWhere('student_number', 'like', "%{$search}%")
                ->orWhere('national_student_number', 'like', "%{$search}%")));
    }

    private function classroomSheet(Classroom $classroom, ?AcademicYear $academicYear, Collection $enrollments): string
    {
        $rows = [
            ['', '', 'YAYASAN MUBAROKAH BINTORO DEMAK', '', '', ''],
            ['', '', 'MADRASAH IBTIDAIYAH MUSLIMAT NU DEMAK', '', '', ''],
            ['', '', 'DATA SISWA', '', '', ''],
            ['', '', 'TAHUN AJARAN '.($academicYear?->name ?? '-'), '', '', ''],
            [],
            ['NO', 'KELAS', 'NAMA ANAK', 'JK', '', 'KETERANGAN'],
            ['', '', '', 'L', 'P', ''],
        ];

        $male = 0;
        $female = 0;
        foreach ($enrollments->values() as $index => $enrollment) {
            $student = $enrollment->student;
            $isMale = $student?->gender === Gender::Male;
            $male += $isMale ? 1 : 0;
            $female += $student?->gender === Gender::Female ? 1 : 0;
            $rows[] = [$index + 1, $classroom->code, $student?->name, $isMale ? '√' : '', $student?->gender === Gender::Female ? '√' : '', $student?->student_status?->label()];
        }

        $rows[] = [];
        $rows[] = ['', '', 'JUMLAH', $male, $female, ''];
        $rows[] = [];
        $rows[] = ['', 'Mengetahui,', '', '', 'Demak, '.now()->translatedFormat('j F Y'), ''];
        $rows[] = ['', 'Kepala Madrasah', '', '', 'Wali Kelas '.$classroom->name, ''];
        $rows[] = [];
        $rows[] = [];
        $rows[] = ['', '', '', '', $classroom->homeroomTeacher?->name ?? '-', ''];

        return $this->sheetXml($rows, [1, 2, 3, 4], [6, 7, count($rows) - 4, count($rows)]);
    }

    private function recapSheet(Collection $students, Collection $classrooms, ?AcademicYear $academicYear): string
    {
        $rows = [['REKAP DATA SISWA'], ['TAHUN AJARAN '.($academicYear?->name ?? '-')], [], ['Kelas', 'Laki-laki', 'Perempuan', 'Jumlah']];
        foreach ($classrooms as $classroom) {
            $classStudents = $students->filter(fn ($student) => $student->activeEnrollment?->classroom_id === $classroom->id);
            $rows[] = [$classroom->code, $classStudents->where('gender', Gender::Male)->count(), $classStudents->where('gender', Gender::Female)->count(), $classStudents->count()];
        }
        $rows[] = ['TOTAL', $students->where('gender', Gender::Male)->count(), $students->where('gender', Gender::Female)->count(), $students->count()];

        return $this->sheetXml($rows, [1, 2], [4, count($rows)]);
    }

    private function sheetXml(array $rows, array $centerRows = [], array $boldRows = []): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView showGridLines="1" workbookViewId="0"/></sheetViews><cols><col min="1" max="1" width="5" customWidth="1"/><col min="2" max="2" width="12" customWidth="1"/><col min="3" max="3" width="36" customWidth="1"/><col min="4" max="5" width="5" customWidth="1"/><col min="6" max="6" width="18" customWidth="1"/></cols><sheetData>';
        foreach ($rows as $r => $row) {
            $style = in_array($r + 1, $boldRows, true) ? 2 : (in_array($r + 1, $centerRows, true) ? 1 : 0);
            $xml .= '<row r="'.($r + 1).'">';
            foreach ($row as $c => $value) {
                $xml .= '<c r="'.$this->cell($c + 1, $r + 1).'" t="inlineStr" s="'.$style.'"><is><t>'.htmlspecialchars((string) $value, ENT_XML1).'</t></is></c>';
            }
            $xml .= '</row>';
        }
        return $xml.'</sheetData><mergeCells count="4"><mergeCell ref="C1:F1"/><mergeCell ref="C2:F2"/><mergeCell ref="C3:F3"/><mergeCell ref="C4:F4"/></mergeCells></worksheet>';
    }

    private function writeWorkbook(array $sheets): string
    {
        $path = tempnam(sys_get_temp_dir(), 'siswa-rekap-').'.xlsx';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'.collect($sheets)->keys()->map(fn ($i) => '<Override PartName="/xl/worksheets/sheet'.($i + 1).'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>')->implode('').'</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>'.collect($sheets)->map(fn ($s, $i) => '<sheet name="'.htmlspecialchars($s['name'], ENT_XML1).'" sheetId="'.($i + 1).'" r:id="rId'.($i + 1).'"/>')->implode('').'</sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.collect($sheets)->keys()->map(fn ($i) => '<Relationship Id="rId'.($i + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.($i + 1).'.xml"/>')->implode('').'<Relationship Id="rId'.(count($sheets) + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Bookman Old Style"/></font><font><b/><sz val="11"/><name val="Bookman Old Style"/></font></fonts><fills count="1"><fill><patternFill patternType="none"/></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center"/></xf><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center"/></xf></cellXfs></styleSheet>');
        foreach ($sheets as $i => $sheet) $zip->addFromString('xl/worksheets/sheet'.($i + 1).'.xml', $sheet['xml']);
        $zip->close();
        return $path;
    }

    private function cell(int $column, int $row): string { $name = ''; while ($column > 0) { $column--; $name = chr(65 + ($column % 26)).$name; $column = intdiv($column, 26); } return $name.$row; }
    private function sheetName(string $name): string { return mb_substr(preg_replace('/[\\/*?:\[\]]/', '-', $name) ?: 'KELAS', 0, 31); }
}
