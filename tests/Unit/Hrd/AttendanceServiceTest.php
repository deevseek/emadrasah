<?php

declare(strict_types=1);

namespace Tests\Unit\Hrd;

use App\Services\Hrd\AttendanceService;
use App\Services\Settings\ApplicationSettingService;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class AttendanceServiceTest extends TestCase
{
    private function service(array $values = []): AttendanceService
    {
        $settings = new class($values) extends ApplicationSettingService {
            public function __construct(private array $values) {}
            public function get(string $key, mixed $fallback = null): mixed { return $this->values[$key] ?? $fallback; }
        };

        return new AttendanceService($settings);
    }

    public function test_haversine_distance_accepts_nearby_points(): void
    {
        $distance = $this->service()->distanceMeters(-6.200000, 106.816666, -6.200050, 106.816666);
        self::assertGreaterThan(5, $distance);
        self::assertLessThan(6, $distance);
    }

    public function test_normal_shift_window_ends_on_same_day(): void
    {
        [$start, $end] = $this->service(['hrd_shift_1_start' => '07:00', 'hrd_shift_1_end' => '15:00'])->shiftWindow(1, CarbonImmutable::parse('2026-08-15'));
        self::assertSame('2026-08-15 07:00', $start->format('Y-m-d H:i'));
        self::assertSame('2026-08-15 15:00', $end->format('Y-m-d H:i'));
    }

    public function test_overnight_shift_window_ends_next_day(): void
    {
        [$start, $end] = $this->service(['hrd_shift_3_start' => '22:00', 'hrd_shift_3_end' => '06:00'])->shiftWindow(3, CarbonImmutable::parse('2026-08-15'));
        self::assertSame('2026-08-15 22:00', $start->format('Y-m-d H:i'));
        self::assertSame('2026-08-16 06:00', $end->format('Y-m-d H:i'));
    }
}
