<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\Academic\ScheduleController;
use App\Http\Controllers\Academic\TeachingAssignmentController;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AcademicExportResponseTypeTest extends TestCase
{
    #[DataProvider('exportControllers')]
    public function test_export_return_type_matches_stream_download_response(string $controller): void
    {
        $returnType = (new ReflectionMethod($controller, 'export'))->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame(StreamedResponse::class, $returnType->getName());
    }

    public static function exportControllers(): array
    {
        return [
            'penugasan mengajar' => [TeachingAssignmentController::class],
            'jadwal pelajaran' => [ScheduleController::class],
        ];
    }
}
