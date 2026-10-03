<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{AcademicYear, Classroom, ClassroomMembership, GradeLevel, Semester, Student, StudentAttendance, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use ZipArchive;

class StudentAttendanceXlsxExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_download_formal_monthly_attendance_report(): void
    {
        [$user, $classroom, $student, $semester] = $this->records();
        StudentAttendance::create(['academic_year_id' => $classroom->academic_year_id, 'semester_id' => $semester->id, 'classroom_id' => $classroom->id, 'student_id' => $student->id, 'attendance_date' => '2026-09-02', 'status' => 'sick', 'recorded_by' => $user->id]);
        StudentAttendance::create(['academic_year_id' => $classroom->academic_year_id, 'semester_id' => $semester->id, 'classroom_id' => $classroom->id, 'student_id' => $student->id, 'attendance_date' => '2026-09-03', 'status' => 'permitted', 'recorded_by' => $user->id]);
        StudentAttendance::create(['academic_year_id' => $classroom->academic_year_id, 'semester_id' => $semester->id, 'classroom_id' => $classroom->id, 'student_id' => $student->id, 'attendance_date' => '2026-09-04', 'status' => 'absent', 'recorded_by' => $user->id]);

        $response = $this->actingAs($user)->get(route('academic.attendance.export', ['classroom_id' => $classroom->id, 'month' => '2026-09']))->assertOk();
        $response->assertDownload('laporan-absensi-kelas-1-a-2026-09.xlsx');
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($response->baseResponse->getFile()->getPathname()) === true);
        $sheet = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        $this->assertStringContainsString('LAPORAN ABSENSI SISWA BULANAN', $sheet);
        $this->assertStringContainsString('Ahmad Falah', $sheet);
        $this->assertStringContainsString('Keterangan: H = Hadir, S = Sakit, I = Izin, A = Alpa', $sheet);
        $this->assertStringContainsString('orientation="landscape"', $sheet);
        $this->assertStringContainsString('<c r="AI9" t="inlineStr" s="2"><is><t xml:space="preserve">1</t>', $sheet);
        $this->assertStringContainsString('<c r="AJ9" t="inlineStr" s="2"><is><t xml:space="preserve">1</t>', $sheet);
        $this->assertStringContainsString('<c r="AK9" t="inlineStr" s="2"><is><t xml:space="preserve">1</t>', $sheet);
    }

    public function test_export_requires_classroom_and_valid_month(): void
    {
        [$user] = $this->records();
        $this->actingAs($user)->get(route('academic.attendance.export', ['month' => 'September']))->assertSessionHasErrors(['classroom_id', 'month']);
    }

    private function records(): array
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo(Permission::findOrCreate('academic-attendance.view'));
        $year = AcademicYear::create(['name' => '2026/2027', 'starts_at' => '2026-07-01', 'ends_at' => '2027-06-30', 'is_active' => true]);
        $semester = Semester::create(['academic_year_id' => $year->id, 'name' => 'Ganjil', 'type' => 'ganjil', 'starts_at' => '2026-07-01', 'ends_at' => '2026-12-31', 'is_active' => true]);
        $grade = GradeLevel::create(['number' => 1, 'name' => 'Kelas 1', 'roman_label' => 'I', 'sort_order' => 1, 'is_active' => true]);
        $classroom = Classroom::create(['academic_year_id' => $year->id, 'grade_level_id' => $grade->id, 'code' => 'A', 'is_active' => true]);
        $student = Student::create(['full_name' => 'Ahmad Falah', 'gender' => 'male', 'status' => 'active']);
        ClassroomMembership::create(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'academic_year_id' => $year->id, 'status' => 'active', 'joined_at' => '2026-07-01']);
        return [$user, $classroom, $student, $semester];
    }
}
