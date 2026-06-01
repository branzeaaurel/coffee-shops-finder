<?php

declare(strict_types=1);

namespace App\Domain\Location;

final readonly class Coordinates
{
    public function __construct(
        public float $x,
        public float $y,
    ) {
        if (!is_finite($x) || !is_finite($y)) {
            throw new \InvalidArgumentException('Coordinates must be finite numbers.');
        }
    }
}
