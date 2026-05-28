<?php

declare(strict_types=1);

namespace App\Domain\Location;

final readonly class NamedLocation
{
    public function __construct(
        public string $name,
        public Coordinates $coordinates,
    ) {
        if ('' === trim($name)) {
            throw new \InvalidArgumentException('NamedLocation name cannot be empty.');
        }
    }
}
