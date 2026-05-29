<?php

declare(strict_types=1);

namespace App\Application\CoffeeShop;

use App\Domain\Location\Coordinates;
use App\Domain\Location\LocationWithDistance;
use App\Domain\Location\NearestLocationsFinder;

final readonly class FindNearestCoffeeShopsHandler
{
    public function __construct(
        private CoffeeShopProviderInterface $provider,
        private NearestLocationsFinder $nearestLocationsFinder,
    ) {
    }

    /**
     * @return list<NearestCoffeeShop>
     */
    public function handle(Coordinates $coordinates, int $limit = 3): array
    {
        $nearest = $this->nearestLocationsFinder->findNearest(
            $coordinates,
            $this->provider->getAll(),
            $limit,
        );

        $shops = [];
        foreach ($nearest as $locationWithDistance) {
            $shops[] = $this->mapResult($locationWithDistance);
        }

        return $shops;
    }

    private function mapResult(LocationWithDistance $locationWithDistance): NearestCoffeeShop
    {
        return new NearestCoffeeShop(
            $locationWithDistance->location->name,
            $locationWithDistance->location->coordinates->x,
            $locationWithDistance->location->coordinates->y,
            $locationWithDistance->distance,
        );
    }
}
