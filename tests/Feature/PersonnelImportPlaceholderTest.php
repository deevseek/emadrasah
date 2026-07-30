<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Personnel;
use App\Models\User;
use App\Services\Personnel\PersonnelDuplicateService;
use App\Services\Personnel\PersonnelImportService;
use App\Services\Personnel\SimpleXlsxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PersonnelImportPlaceholderTest extends TestCase
{
    use RefreshDatabase;

    public function test_placeholders_and_excel_numbers_are_normalized_without_losing_leading_zeroes(): void
    {
        foreach (['-', '–', '—', 'N/A', 'NULL'] as $index => $placeholder) {
            $batch = $this->preview([$this->row($index + 1, $placeholder, '00'.($index + 1), [
                16 => '001234.0',
                17 => '089681929596.0',
            ])]);
            $data = $batch->rows()->firstOrFail()->normalized_data;

            $this->assertNull($data['nip']);
            $this->assertSame('00'.($index + 1), $data['foundation_employee_number']);
            $this->assertSame('001234', $data['bank_account_number']);
            $this->assertSame('089681929596', $data['phone']);
        }
    }

    public function test_valid_nip_and_niy_remain_strings(): void
    {
        $data = $this->preview([$this->row(1, '6200720001.0', '001601028750535')])
            ->rows()->firstOrFail()->normalized_data;

        $this->assertSame('6200720001', $data['nip']);
        $this->assertSame('001601028750535', $data['foundation_employee_number']);
    }

    public function test_placeholder_identifiers_are_not_used_for_duplicate_detection(): void
    {
        Personnel::create($this->personnel(['nip' => '-']));

        foreach (['-', '', '—', 'N/A'] as $nip) {
            $result = app(PersonnelDuplicateService::class)->find([
                'full_name' => 'Orang Berbeda',
                'birth_date' => '2000-02-02',
                'nip' => $nip,
            ]);

            $this->assertNull($result['match']);
            $this->assertFalse($result['conflict']);
            $this->assertNull($result['matched_by']);
        }
    }

    public function test_valid_identifiers_are_reported_as_duplicate_sources(): void
    {
        $personnel = Personnel::create($this->personnel([
            'foundation_employee_number' => 'NIY-1',
            'nip' => 'NIP-1',
        ]));

        $byNiy = app(PersonnelDuplicateService::class)->find(['foundation_employee_number' => 'NIY-1']);
        $byNip = app(PersonnelDuplicateService::class)->find(['nip' => 'NIP-1']);

        $this->assertTrue($personnel->is($byNiy['match']));
        $this->assertSame('foundation_employee_number', $byNiy['matched_by']);
        $this->assertTrue($personnel->is($byNip['match']));
        $this->assertSame('nip', $byNip['matched_by']);
    }

    public function test_twenty_five_placeholder_nips_can_be_previewed_and_imported(): void
    {
        $rows = [];
        for ($number = 1; $number <= 25; $number++) {
            $rows[] = $this->row($number, '-', sprintf('NIY-%03d', $number));
        }

        $batch = $this->preview($rows);

        $this->assertSame(25, $batch->total_rows);
        $this->assertSame(25, $batch->valid_rows);
        $this->assertSame(0, $batch->duplicate_rows);
        $this->assertSame(0, $batch->invalid_rows);

        app(PersonnelImportService::class)->confirm($batch, User::findOrFail($batch->user_id));

        $this->assertDatabaseCount('personnel', 25);
        $this->assertSame(25, $batch->fresh()->imported_rows);
        $this->assertSame(0, Personnel::whereNotNull('nip')->count());
    }

    public function test_existing_first_row_is_skipped_by_niy_and_other_rows_are_imported(): void
    {
        Personnel::create($this->personnel(['foundation_employee_number' => 'NIY-001']));
        $rows = [];
        for ($number = 1; $number <= 25; $number++) {
            $rows[] = $this->row($number, '-', sprintf('NIY-%03d', $number));
        }

        $batch = $this->preview($rows);

        $this->assertSame(24, $batch->valid_rows);
        $this->assertSame(1, $batch->duplicate_rows);
        $this->assertStringContainsString('berdasarkan NIY', implode(' ', $batch->rows()->where('status', 'duplicate')->firstOrFail()->messages));

        app(PersonnelImportService::class)->confirm($batch, User::findOrFail($batch->user_id));

        $this->assertDatabaseCount('personnel', 25);
        $this->assertSame(24, $batch->fresh()->imported_rows);
        $this->assertSame(1, $batch->fresh()->skipped_rows);
    }

    public function test_cleanup_migration_nulls_placeholders_without_changing_valid_data(): void
    {
        $placeholder = Personnel::create($this->personnel(['full_name' => 'Placeholder', 'nip' => '-']));
        $valid = Personnel::create($this->personnel(['full_name' => 'Valid', 'nip' => '0012345678']));
        $migration = require database_path('migrations/2026_07_30_410000_normalize_personnel_placeholder_values.php');

        $migration->up();

        $this->assertNull(DB::table('personnel')->where('id', $placeholder->id)->value('nip'));
        $this->assertSame('0012345678', DB::table('personnel')->where('id', $valid->id)->value('nip'));
    }

    private function preview(array $rows)
    {
        Storage::fake('local');
        $path = tempnam(sys_get_temp_dir(), 'personnel').'.xlsx';
        app(SimpleXlsxService::class)->write([$this->headers(), ...$rows], $path);
        $file = new UploadedFile($path, 'personnel.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        return app(PersonnelImportService::class)->preview($file, 'skip', User::factory()->create());
    }

    private function headers(): array
    {
        return PersonnelImportService::HEADERS;
    }

    private function row(int $number, string $nip, string $niy, array $overrides = []): array
    {
        return array_replace([
            1 => $number,
            2 => 'Personalia '.$number,
            3 => 'L',
            4 => 'Bandung, 01 Januari '.(1980 + $number),
            5 => 'TETAP',
            6 => $niy,
            7 => $nip,
            8 => '-',
            9 => '-',
            10 => 'S1',
            11 => 'Guru',
            12 => '-',
            13 => '-',
            14 => '24',
            15 => '-',
            16 => '-',
            17 => '-',
            18 => '-',
        ], $overrides);
    }

    private function personnel(array $overrides = []): array
    {
        return array_replace([
            'full_name' => 'Personalia Lama',
            'gender' => 'male',
            'birth_date' => '1980-01-01',
            'employment_status' => 'TETAP',
            'position' => 'Guru',
            'is_active' => true,
        ], $overrides);
    }
}
