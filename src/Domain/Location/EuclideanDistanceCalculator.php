<?php

declare(strict_types=1);

namespace App\Domain\Location;

final readonly class EuclideanDistanceCalculator implements DistanceCalculatorInterface
{
    public function calculate(Coordinates $from, Coordinates $to): float
    {
        $dx = $from->x - $to->x;
        $dy = $from->y - $to->y;

        return sqrt($dx * $dx + $dy * $dy);
    }
}
