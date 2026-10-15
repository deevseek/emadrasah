<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Enums\AttendanceStatus;
use App\Models\{Classroom, SchoolProfile, Student, StudentAttendance, User};
use App\Services\Foundation\SchoolProfileService;
use Carbon\CarbonImmutable;
use RuntimeException;
use ZipArchive;

class StudentAttendanceXlsxService
{
    public function __construct(private readonly SchoolProfileService $profiles) {}

    public function export(Classroom $classroom, CarbonImmutable $month, User $actor): string
    {
        $classroom->loadMissing(['academicYear', 'gradeLevel', 'homeroomPersonnel']);
        // Jangan hanya memakai relasi students() karena relasi tersebut hanya memuat
        // keanggotaan yang berstatus aktif saat ini. Laporan bulan lampau tetap harus
        // menampilkan siswa yang sudah pindah/keluar dan setiap siswa yang mempunyai
        // catatan absensi pada periode yang diminta.
        $students = Student::query()
            ->where(function ($query) use ($classroom, $month): void {
                $query->whereHas('classroomMemberships', function ($membership) use ($classroom, $month): void {
                    $membership->where('classroom_id', $classroom->id)
                        ->whereDate('joined_at', '<=', $month->endOfMonth())
                        ->where(fn ($dates) => $dates->whereNull('left_at')->orWhereDate('left_at', '>=', $month->startOfMonth()));
                })->orWhereHas('attendances', fn ($records) => $records
                    ->where('classroom_id', $classroom->id)
                    ->whereBetween('attendance_date', [$month->startOfMonth(), $month->endOfMonth()]));
            })
            ->orderBy('full_name')
            ->get();
        $attendance = StudentAttendance::query()->where('classroom_id', $classroom->id)
            ->whereBetween('attendance_date', [$month->startOfMonth(), $month->endOfMonth()])->get()
            ->keyBy(fn (StudentAttendance $record): string => $record->student_id.'-'.$record->attendance_date->day);
        $path = tempnam(sys_get_temp_dir(), 'laporan-absensi-');
        if ($path === false) throw new RuntimeException('File laporan absensi tidak dapat dibuat.');
        try {
            $this->write($path, $classroom, $month, $students, $attendance, $this->profiles->current());
        } catch (\Throwable $exception) {
            @unlink($path); throw $exception;
        }
        activity('academic-attendance')->causedBy($actor)->withProperties(['classroom_id' => $classroom->id, 'month' => $month->format('Y-m'), 'student_count' => $students->count()])->log('Mengunduh laporan absensi siswa bulanan dalam format XLSX.');
        return $path;
    }

    public function filename(Classroom $classroom, CarbonImmutable $month): string
    {
        return 'laporan-absensi-'.str($classroom->display_name)->slug().'-'.$month->format('Y-m').'.xlsx';
    }

    private function write(string $path, Classroom $classroom, CarbonImmutable $month, $students, $attendance, SchoolProfile $school): void
    {
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new RuntimeException('File XLSX laporan absensi tidak dapat dibuat.');
        $files = [
            '[Content_Types].xml' => '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Absensi '.$this->escape($month->translatedFormat('F Y')).'" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
            'xl/styles.xml' => $this->stylesXml(), 'xl/worksheets/sheet1.xml' => $this->sheetXml($classroom, $month, $students, $attendance, $school),
        ];
        foreach ($files as $name => $contents) {
            if (! $zip->addFromString($name, $contents)) {
                $zip->close();
                throw new RuntimeException('Isi file XLSX laporan absensi tidak dapat ditulis.');
            }
        }
        if (! $zip->close() || ! is_file($path) || filesize($path) === 0) {
            throw new RuntimeException('File XLSX laporan absensi gagal diselesaikan.');
        }
    }

    private function sheetXml(Classroom $classroom, CarbonImmutable $month, $students, $attendance, SchoolProfile $school): string
    {
        $rows = $this->row(1, [[$school->name ?: $school->display_name, 3]], 28)
            .$this->row(2, [['LAPORAN ABSENSI SISWA BULANAN', 3]], 25)
            .$this->row(3, [[$school->display_address ?: 'Alamat madrasah belum dilengkapi', 4]], 20)
            .$this->row(5, $this->positioned([0 => ['Bulan', 5], 1 => [$month->translatedFormat('F Y'), 6], 8 => ['Rombel', 5], 9 => [$classroom->display_name, 6]]))
            .$this->row(6, $this->positioned([0 => ['Tahun Ajaran', 5], 1 => [$classroom->academicYear->name, 6], 8 => ['Wali Kelas', 5], 9 => [$classroom->homeroomPersonnel?->full_name ?: 'Belum ditetapkan', 6]]));
        $header = [['NO', 1], ['NAMA SISWA', 1], ['L/P', 1]];
        for ($day = 1; $day <= 31; $day++) $header[] = [(string) $day, $day > $month->daysInMonth ? 8 : 1];
        $rows .= $this->row(8, [...$header, ['S', 1], ['I', 1], ['A', 1], ['KETERANGAN', 1]], 30);
        $rowNumber = 9;
        foreach ($students as $index => $student) {
            $counts = ['S' => 0, 'I' => 0, 'A' => 0];
            $cells = [[(string) ($index + 1), 2], [$student->full_name, 7], [$student->gender === 'male' ? 'L' : 'P', 2]];
            for ($day = 1; $day <= 31; $day++) {
                $record = $attendance->get($student->id.'-'.$day); $code = $record ? $this->statusCode($record->status) : '';
                if (isset($counts[$code])) $counts[$code]++;
                $cells[] = [$code, $day > $month->daysInMonth ? 8 : 2];
            }
            $rows .= $this->row($rowNumber++, [...$cells, [(string) $counts['S'], 2], [(string) $counts['I'], 2], [(string) $counts['A'], 2], ['', 7]], 21);
        }
        if ($students->isEmpty()) $rows .= $this->row($rowNumber++, [['Belum ada siswa aktif pada rombel ini.', 9]], 26);
        $legendRow = $rowNumber + 1;
        $rows .= $this->row($legendRow, [['Keterangan: H = Hadir, S = Sakit, I = Izin, A = Alpa', 4]]);
        $signatureRow = $legendRow + 3; $location = $school->city ?: $school->district ?: '....................';
        $rows .= $this->row($signatureRow, $this->positioned([0 => ['Mengetahui,', 4], 28 => [$location.', '.$month->endOfMonth()->translatedFormat('d F Y'), 4]]))
            .$this->row($signatureRow + 1, $this->positioned([0 => ['Kepala Madrasah', 4], 28 => ['Wali Kelas', 4]]))
            .$this->row($signatureRow + 5, $this->positioned([0 => [$school->head_name ?: '................................', 10], 28 => [$classroom->homeroomPersonnel?->full_name ?: '................................', 10]]))
            .$this->row($signatureRow + 6, [['NIP. '.($school->head_nip ?: '................................'), 4]]);
        $merges = ['A1:AM1','A2:AM2','A3:AM3','B5:H5','J5:Q5','B6:H6','J6:Q6'];
        if ($students->isEmpty()) $merges[] = 'A9:AM9';
        $merges = [...$merges, "A{$legendRow}:Q{$legendRow}", "A{$signatureRow}:H{$signatureRow}", "AC{$signatureRow}:AM{$signatureRow}", 'A'.($signatureRow+1).':H'.($signatureRow+1), 'AC'.($signatureRow+1).':AM'.($signatureRow+1), 'A'.($signatureRow+5).':H'.($signatureRow+5), 'AC'.($signatureRow+5).':AM'.($signatureRow+5), 'A'.($signatureRow+6).':H'.($signatureRow+6)];
        $mergeXml = '<mergeCells count="'.count($merges).'">'.collect($merges)->map(fn ($range) => '<mergeCell ref="'.$range.'"/>')->implode('').'</mergeCells>';
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetPr><pageSetUpPr fitToPage="1"/></sheetPr><dimension ref="A1:AM'.($signatureRow+6).'"/><sheetViews><sheetView workbookViewId="0"><pane ySplit="8" topLeftCell="A9" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><sheetFormatPr defaultRowHeight="18"/><cols><col min="1" max="1" width="5" customWidth="1"/><col min="2" max="2" width="31" customWidth="1"/><col min="3" max="3" width="6" customWidth="1"/><col min="4" max="34" width="4" customWidth="1"/><col min="35" max="37" width="6" customWidth="1"/><col min="38" max="39" width="14" customWidth="1"/></cols><sheetData>'.$rows.'</sheetData>'.$mergeXml.'<printOptions horizontalCentered="1"/><pageMargins left="0.2" right="0.2" top="0.4" bottom="0.4" header="0.2" footer="0.2"/><pageSetup orientation="landscape" paperSize="9" fitToWidth="1" fitToHeight="1"/></worksheet>';
    }

    private function positioned(array $cells): array
    {
        $positioned = array_fill(0, max(array_keys($cells)) + 1, ['', 0]);
        foreach ($cells as $index => $cell) $positioned[$index] = $cell;
        return $positioned;
    }

    private function row(int $number, array $cells, int $height = 18): string
    {
        $xml = '<row r="'.$number.'" ht="'.$height.'" customHeight="1">';
        foreach ($cells as $index => [$value, $style]) { if ($value === '' && $style === 0) continue; $reference = $this->columnName($index + 1).$number; $xml .= '<c r="'.$reference.'" t="inlineStr" s="'.$style.'"><is><t xml:space="preserve">'.$this->escape($value).'</t></is></c>'; }
        return $xml.'</row>';
    }

    private function statusCode(AttendanceStatus $status): string { return match ($status) { AttendanceStatus::Present => 'H', AttendanceStatus::Sick => 'S', AttendanceStatus::Permitted => 'I', AttendanceStatus::Absent => 'A' }; }
    private function stylesXml(): string { return '<?xml version="1.0"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="5"><font><sz val="10"/><name val="Arial"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="10"/><name val="Arial"/></font><font><b/><sz val="16"/><name val="Arial"/></font><font><b/><sz val="13"/><name val="Arial"/></font><font><u val="single"/><b/><sz val="10"/><name val="Arial"/></font></fonts><fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF14532D"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFE5E7EB"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="2"><border/><border><left style="thin"/><right style="thin"/><top style="thin"/><bottom style="thin"/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="11"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="2" fillId="0" borderId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="0" applyAlignment="1"><alignment horizontal="center"/></xf><xf numFmtId="0" fontId="1" fillId="0" borderId="0" applyFont="1"/><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/><xf numFmtId="0" fontId="0" fillId="0" borderId="1" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf><xf numFmtId="0" fontId="0" fillId="3" borderId="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="1" fillId="0" borderId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center"/></xf><xf numFmtId="0" fontId="4" fillId="0" borderId="0" applyFont="1"/></cellXfs></styleSheet>'; }
    private function escape(mixed $value): string { return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8'); }
    private function columnName(int $number): string { $name=''; while ($number) { $number--; $name=chr(65+$number%26).$name; $number=intdiv($number,26); } return $name; }
}
