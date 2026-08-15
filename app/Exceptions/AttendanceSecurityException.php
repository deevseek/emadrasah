<?php
declare(strict_types=1);
namespace App\Exceptions;
use RuntimeException;
class AttendanceSecurityException extends RuntimeException{public function __construct(public readonly string $errorCode,string $message,public readonly int $status=422){parent::__construct($message);}}
