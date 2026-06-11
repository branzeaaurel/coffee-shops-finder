<?php

declare(strict_types=1);

namespace App\GraphQl;

use App\Application\CoffeeShop\FindNearestCoffeeShopsHandler;
use App\Application\CoffeeShop\NearestCoffeeShop;
use App\Domain\Location\Coordinates;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;

final readonly class CoffeeShopSchemaFactory
{
    private const RESULT_LIMIT = 3;

    public function __construct(
        private FindNearestCoffeeShopsHandler $handler,
    ) {
    }

    public function create(): Schema
    {
        $location = new ObjectType([
            'name' => 'Location',
            'fields' => [
                'x' => Type::nonNull(Type::float()),
                'y' => Type::nonNull(Type::float()),
            ],
        ]);

        $coffeeShop = new ObjectType([
            'name' => 'CoffeeShop',
            'fields' => [
                'name' => Type::nonNull(Type::string()),
                'location' => [
                    'type' => Type::nonNull($location),
                    'resolve' => static fn (NearestCoffeeShop $shop): array => ['x' => $shop->x, 'y' => $shop->y],
                ],
                'distance' => [
                    'type' => Type::nonNull(Type::float()),
                    'resolve' => static fn (NearestCoffeeShop $shop): float => round($shop->distance, 4),
                ],
            ],
        ]);

        $query = new ObjectType([
            'name' => 'Query',
            'fields' => [
                'nearestCoffeeShops' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull($coffeeShop))),
                    'args' => [
                        'x' => Type::nonNull(Type::float()),
                        'y' => Type::nonNull(Type::float()),
                    ],
                    'resolve' => fn (mixed $root, array $args): array => $this->handler->handle(
                        new Coordinates((float) $args['x'], (float) $args['y']),
                        self::RESULT_LIMIT,
                    ),
                ],
            ],
        ]);

        return new Schema((new SchemaConfig())->setQuery($query));
    }
}
