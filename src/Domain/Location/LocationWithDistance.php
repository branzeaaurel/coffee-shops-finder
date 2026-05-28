<?php

declare(strict_types=1);

namespace App\Domain\Location;

final readonly class LocationWithDistance
{
    public function __construct(
        public NamedLocation $location,
        public float $distance,
    ) {
        if (!is_finite($distance) || $distance < 0.0) {
            throw new \InvalidArgumentException('Distance must be a non-negative finite number.');
        }
    }
}
