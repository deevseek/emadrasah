<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class RfidUidConflictException extends RuntimeException
{
    public const ERROR_CODE = 'RFID_UID_ASSIGNED_TO_OTHER_STUDENT';

    public function __construct()
    {
        parent::__construct('UID kartu sudah terdaftar pada siswa lain.');
    }
}
