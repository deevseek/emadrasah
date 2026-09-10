<?php

declare(strict_types=1);

namespace App\Enums;

enum RfidAttendanceResultCode: string
{
    case AttendanceCreated = 'ATTENDANCE_CREATED';
    case AlreadyAttended = 'ALREADY_ATTENDED';
    case ManualStatusLocked = 'MANUAL_STATUS_LOCKED';
    case RfidDisabled = 'RFID_DISABLED';
    case CardNotProvisioned = 'CARD_NOT_PROVISIONED';
    case CardNotRegistered = 'CARD_NOT_REGISTERED';
    case CardUidMismatch = 'CARD_UID_MISMATCH';
    case AcademicContextMissing = 'ACADEMIC_CONTEXT_MISSING';
}
