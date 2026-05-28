<?php

declare(strict_types=1);

namespace App\Domain\Location;

final readonly class NearestLocationsFinder
{
    public function __construct(
        private DistanceCalculatorInterface $distanceCalculator,
    ) {
    }

    /**
     * @param iterable<NamedLocation> $locations
     *
     * @return list<LocationWithDistance>
     */
    public function findNearest(Coordinates $origin, iterable $locations, int $limit = 3): array
    {
        if ($limit < 1) {
            throw new \InvalidArgumentException('Limit must be at least 1.');
        }

        /** @var list<LocationWithDistance> $nearest */
        $nearest = [];

        foreach ($locations as $location) {
            $distance = $this->distanceCalculator->calculate($origin, $location->coordinates);

            $size = \count($nearest);
            if ($size === $limit && $distance >= $nearest[$size - 1]->distance) {
                continue;
            }

            $nearest = $this->insertCandidate($nearest, new LocationWithDistance($location, $distance), $limit);
        }

        return $nearest;
    }

    /**
     * @param list<LocationWithDistance> $nearest
     *
     * @return list<LocationWithDistance>
     */
    private function insertCandidate(array $nearest, LocationWithDistance $candidate, int $limit): array
    {
        $insertAt = \count($nearest);

        foreach ($nearest as $index => $current) {
            if ($candidate->distance < $current->distance) {
                $insertAt = $index;
                break;
            }
        }

        array_splice($nearest, $insertAt, 0, [$candidate]);

        if (\count($nearest) > $limit) {
            array_pop($nearest);
        }

        return $nearest;
    }
}
