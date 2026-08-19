<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Throwable;

final class BriApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $responseCode = null,
        public readonly ?int $httpStatus = null,
        public readonly bool $outcomeUnknown = false,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
