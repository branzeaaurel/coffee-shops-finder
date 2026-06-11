<?php

declare(strict_types=1);

namespace App\Http\Error;

final readonly class ApiError
{
    public function __construct(
        public string $code,
        public string $message,
        public int $status,
    ) {
    }
}
