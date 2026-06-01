<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\CoffeeShop;

use App\Application\CoffeeShop\CoffeeShopProviderInterface;
use App\Application\CoffeeShop\FindNearestCoffeeShopsHandler;
use App\Application\CoffeeShop\NearestCoffeeShop;
use App\Domain\Location\Coordinates;
use App\Domain\Location\EuclideanDistanceCalculator;
use App\Domain\Location\NamedLocation;
use App\Domain\Location\NearestLocationsFinder;
use PHPUnit\Framework\TestCase;

final class FindNearestCoffeeShopsHandlerTest extends TestCase
{
    public function testReturnsThreeNearestCoffeeShopsOrderedByDistance(): void
    {
        $handler = $this->handler([
            self::location('Far', 10.0, 10.0),
            self::location('Closest', 0.1, 0.0),
            self::location('Third', 1.0, 1.0),
            self::location('Second', 0.5, 0.5),
            self::location('Beyond', 100.0, 100.0),
        ]);

        $result = $handler->handle(new Coordinates(0.0, 0.0));

        self::assertSame(['Closest', 'Second', 'Third'], self::names($result));
    }

    public function testRespectsCustomLimit(): void
    {
        $handler = $this->handler([
            self::location('A', 1.0, 0.0),
            self::location('B', 2.0, 0.0),
            self::location('C', 3.0, 0.0),
        ]);

        $result = $handler->handle(new Coordinates(0.0, 0.0), 2);

        self::assertSame(['A', 'B'], self::names($result));
    }

    public function testReturnsEmptyArrayWhenProviderHasNoLocations(): void
    {
        $handler = $this->handler([]);

        $result = $handler->handle(new Coordinates(0.0, 0.0));

        self::assertSame([], $result);
    }

    public function testWorksWhenProviderReturnsGenerator(): void
    {
        $generator = (static function (): \Generator {
            yield self::location('A', 1.0, 0.0);
            yield self::location('B', 0.0, 0.5);
        })();

        $handler = $this->handler($generator);

        $result = $handler->handle(new Coordinates(0.0, 0.0));

        self::assertSame(['B', 'A'], self::names($result));
    }

    public function testDistanceIsRawAndNotRounded(): void
    {
        $handler = $this->handler([
            self::location('Point', 1.0, 1.0),
        ]);

        $result = $handler->handle(new Coordinates(0.0, 0.0));

        self::assertSame(sqrt(2.0), $result[0]->distance);
    }

    public function testMapsNameCoordinatesAndDistance(): void
    {
        $handler = $this->handler([
            self::location('Origin Shop', 3.0, 4.0),
        ]);

        $result = $handler->handle(new Coordinates(0.0, 0.0));

        self::assertCount(1, $result);
        self::assertSame('Origin Shop', $result[0]->name);
        self::assertSame(3.0, $result[0]->x);
        self::assertSame(4.0, $result[0]->y);
        self::assertSame(5.0, $result[0]->distance);
    }

    /**
     * @param iterable<NamedLocation> $locations
     */
    private function handler(iterable $locations): FindNearestCoffeeShopsHandler
    {
        $provider = $this->createMock(CoffeeShopProviderInterface::class);
        $provider->expects($this->once())->method('getAll')->willReturn($locations);

        return new FindNearestCoffeeShopsHandler(
            $provider,
            new NearestLocationsFinder(new EuclideanDistanceCalculator()),
        );
    }

    private static function location(string $name, float $x, float $y): NamedLocation
    {
        return new NamedLocation($name, new Coordinates($x, $y));
    }

    /**
     * @param list<NearestCoffeeShop> $shops
     *
     * @return list<string>
     */
    private static function names(array $shops): array
    {
        return array_map(
            static fn (NearestCoffeeShop $shop): string => $shop->name,
            $shops,
        );
    }
}
