<?php

declare(strict_types=1);

namespace App\Domain\Location;

interface DistanceCalculatorInterface
{
    public function calculate(Coordinates $from, Coordinates $to): float;
}
