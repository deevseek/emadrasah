<?php

declare(strict_types=1);

namespace App\Enums;

enum RfidAttendanceResultCode: string
{
    case AttendanceCreated = 'ATTENDANCE_CREATED';
    case AlreadyAttended = 'ALREADY_ATTENDED';
    case ManualStatusLocked = 'MANUAL_STATUS_LOCKED';
}
