<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SubjectCategory;
use App\Models\GradeLevel;
use App\Models\Subject;
use App\Services\Academic\Imports\ImportMatcher;
use Database\Seeders\MimnuSubjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MimnuSubjectSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (range(1, 6) as $level) {
            GradeLevel::create(['name' => "Kelas {$level}", 'code' => "K{$level}", 'level' => $level, 'is_active' => true]);
        }
    }

    public function test_seeder_is_idempotent_and_creates_the_canonical_subjects_only(): void
    {
        $this->seed(MimnuSubjectSeeder::class);
        $this->seed(MimnuSubjectSeeder::class);

        $codes = ['QH', 'AA', 'FIQ', 'SKI', 'BAR', 'PKN', 'BINDO', 'MTK', 'IPAS', 'PJOK', 'SBDP', 'BING', 'BAJA', 'BTAQ', 'TIK', 'KE-NU-AN', 'TKA', 'TAQ', 'NUM', 'LIT', 'LA', 'STEAM'];
        $this->assertSame(22, Subject::count());
        $this->assertEqualsCanonicalizing($codes, Subject::pluck('code')->all());
        $this->assertSame(0, Subject::whereIn('code', ['PAGI', 'TASMI', 'IST'])->count());

        $this->assertLevels('QH', [1, 2, 3, 4, 5, 6]);
        $this->assertLevels('SKI', [3, 4, 5, 6]);
        $this->assertLevels('IPAS', [3, 4, 5, 6]);
        $this->assertLevels('TKA', [6]);
        $this->assertLevels('TAQ', [1]);
        $this->assertLevels('STEAM', [1, 2, 3, 4, 5]);
    }

    public function test_legacy_aliases_are_migrated_without_changing_the_subject_id(): void
    {
        $legacy = Subject::create(['code' => 'BIN', 'name' => 'Bahasa Indonesia', 'category' => SubjectCategory::General, 'is_active' => true]);
        $legacyAkidah = Subject::create(['code' => 'OLD-AA', 'name' => 'Aqidah Akhlaq', 'category' => SubjectCategory::Religion, 'is_active' => true]);

        $this->seed(MimnuSubjectSeeder::class);

        $this->assertSame($legacy->id, Subject::where('code', 'BINDO')->value('id'));
        $this->assertSame($legacyAkidah->id, Subject::where('code', 'AA')->value('id'));
    }

    public function test_import_matcher_accepts_official_code_variants(): void
    {
        $this->seed(MimnuSubjectSeeder::class);
        $matcher = app(ImportMatcher::class);

        $this->assertSame('SBDP', $matcher->subject('SBdP', null)['model']?->code);
        $this->assertSame('KE-NU-AN', $matcher->subject('Ke-NU-an', null)['model']?->code);
        $this->assertSame('QH', $matcher->subject('unknown', 'Al-Qur’an Hadits')['model']?->code);
    }

    private function assertLevels(string $code, array $expected): void
    {
        $actual = Subject::where('code', $code)->firstOrFail()->gradeLevels()->orderBy('level')->pluck('level')->all();
        $this->assertSame($expected, $actual);
    }
}
