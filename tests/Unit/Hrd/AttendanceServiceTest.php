<?php

declare(strict_types=1);

namespace Tests\Unit\Hrd;

use App\Services\Hrd\AttendanceService;
use App\Services\Settings\ApplicationSettingService;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;
use App\Exceptions\AttendanceSecurityException;

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

    public function test_location_inside_radius_and_accuracy_are_validated(): void
    {
        $now = CarbonImmutable::parse('2026-08-15 07:00:00');
        $result = $this->service(['hrd_attendance_location_enabled'=>true,'hrd_attendance_latitude'=>-6.2,'hrd_attendance_longitude'=>106.816666,'hrd_attendance_radius_meter'=>20,'hrd_attendance_max_accuracy_meter'=>50,'hrd_attendance_location_max_age_seconds'=>30])->validateLocation(['latitude'=>-6.20005,'longitude'=>106.816666,'accuracy'=>10,'location_captured_at'=>$now->toIso8601String()],$now);
        self::assertGreaterThan(5,$result['distance']); self::assertLessThan(6,$result['distance']);
    }

    public function test_location_accuracy_area_that_overlaps_radius_is_accepted(): void
    {
        $now = CarbonImmutable::parse('2026-08-15 07:00:00');
        $result = $this->service(['hrd_attendance_location_enabled'=>true,'hrd_attendance_latitude'=>-6.2,'hrd_attendance_longitude'=>106.816666,'hrd_attendance_radius_meter'=>20,'hrd_attendance_max_accuracy_meter'=>50,'hrd_attendance_location_max_age_seconds'=>30])->validateLocation(['latitude'=>-6.2003,'longitude'=>106.816666,'accuracy'=>14,'location_captured_at'=>$now->toIso8601String()],$now);

        self::assertGreaterThan(33,$result['distance']);
        self::assertLessThan(34,$result['distance']);
    }

    public function test_location_validation_is_skipped_when_disabled(): void
    {
        self::assertSame(['distance'=>null],$this->service(['hrd_attendance_location_enabled'=>false])->validateLocation([]));
    }

    /** @dataProvider rejectedLocations */
    public function test_invalid_location_is_rejected(array $location,string $code): void
    {
        $now=CarbonImmutable::parse('2026-08-15 07:00:00');$service=$this->service(['hrd_attendance_location_enabled'=>true,'hrd_attendance_latitude'=>-6.2,'hrd_attendance_longitude'=>106.816666,'hrd_attendance_radius_meter'=>20,'hrd_attendance_max_accuracy_meter'=>50,'hrd_attendance_location_max_age_seconds'=>30]);
        try{$service->validateLocation($location,$now);self::fail('Lokasi seharusnya ditolak.');}catch(AttendanceSecurityException $e){self::assertSame($code,$e->errorCode);}
    }
    public static function rejectedLocations():array{return[
        'outside'=>[['latitude'=>-6.21,'longitude'=>106.816666,'accuracy'=>10,'location_captured_at'=>'2026-08-15T07:00:00+00:00'],'LOCATION_OUTSIDE_RADIUS'],
        'accuracy'=>[['latitude'=>-6.2,'longitude'=>106.816666,'accuracy'=>51,'location_captured_at'=>'2026-08-15T07:00:00+00:00'],'LOCATION_ACCURACY_TOO_LOW'],
        'expired'=>[['latitude'=>-6.2,'longitude'=>106.816666,'accuracy'=>10,'location_captured_at'=>'2026-08-15T06:59:00+00:00'],'LOCATION_EXPIRED'],
    ];}
}
