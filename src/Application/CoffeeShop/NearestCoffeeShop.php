<?php

declare(strict_types=1);

namespace App\Application\CoffeeShop;

final readonly class NearestCoffeeShop
{
    public function __construct(
        public string $name,
        public float $x,
        public float $y,
        public float $distance,
    ) {
    }
}
