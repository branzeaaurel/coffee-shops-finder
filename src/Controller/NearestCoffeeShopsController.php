<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\CoffeeShop\FindNearestCoffeeShopsHandler;
use App\Application\CoffeeShop\NearestCoffeeShop;
use App\Domain\Location\Coordinates;
use App\Exception\InvalidCoordinatesException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final readonly class NearestCoffeeShopsController
{
    private const RESULT_LIMIT = 3;

    public function __construct(
        private FindNearestCoffeeShopsHandler $handler,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $x = $this->queryCoordinate($request, 'x');
        $y = $this->queryCoordinate($request, 'y');

        $shops = $this->handler->handle(new Coordinates($x, $y), self::RESULT_LIMIT);

        $response = [];
        foreach ($shops as $shop) {
            $response[] = $this->mapCoffeeShop($shop);
        }

        return new JsonResponse($response);
    }

    private function queryCoordinate(Request $request, string $name): float
    {
        $query = $request->query->all();

        if (!\array_key_exists($name, $query)) {
            throw new InvalidCoordinatesException(sprintf('Missing required query parameter "%s".', $name));
        }

        $value = $query[$name];

        if (!is_scalar($value)) {
            throw new InvalidCoordinatesException(sprintf('Query parameter "%s" must be a finite number.', $name));
        }

        $rawValue = trim((string) $value);

        if ('' === $rawValue || !is_numeric($rawValue)) {
            throw new InvalidCoordinatesException(sprintf('Query parameter "%s" must be a finite number.', $name));
        }

        $float = (float) $rawValue;

        if (!is_finite($float)) {
            throw new InvalidCoordinatesException(sprintf('Query parameter "%s" must be a finite number.', $name));
        }

        return $float;
    }

    /**
     * @return array{name: string, location: array{x: float, y: float}, distance: float}
     */
    private function mapCoffeeShop(NearestCoffeeShop $shop): array
    {
        return [
            'name' => $shop->name,
            'location' => ['x' => $shop->x, 'y' => $shop->y],
            'distance' => round($shop->distance, 4),
        ];
    }
}
