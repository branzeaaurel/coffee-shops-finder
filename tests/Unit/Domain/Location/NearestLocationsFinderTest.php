<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Location;

use App\Domain\Location\Coordinates;
use App\Domain\Location\EuclideanDistanceCalculator;
use App\Domain\Location\LocationWithDistance;
use App\Domain\Location\NamedLocation;
use App\Domain\Location\NearestLocationsFinder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NearestLocationsFinderTest extends TestCase
{
    public function testReturnsEmptyArrayForEmptyIterable(): void
    {
        $finder = new NearestLocationsFinder(new EuclideanDistanceCalculator());

        self::assertSame([], $finder->findNearest(new Coordinates(0.0, 0.0), []));
    }

    public function testReturnsFewerThanLimitWhenIterableHasFewer(): void
    {
        $finder = new NearestLocationsFinder(new EuclideanDistanceCalculator());

        $result = $finder->findNearest(new Coordinates(0.0, 0.0), [
            self::location('A', 1.0, 0.0),
            self::location('B', 0.0, 2.0),
        ], 3);

        self::assertSame(['A', 'B'], self::names($result));
    }

    public function testReturnsTopThreeOrderedByAscendingDistance(): void
    {
        $finder = new NearestLocationsFinder(new EuclideanDistanceCalculator());

        $result = $finder->findNearest(new Coordinates(0.0, 0.0), [
            self::location('far', 10.0, 10.0),
            self::location('closest', 0.1, 0.0),
            self::location('mid', 1.0, 1.0),
            self::location('second', 0.5, 0.5),
            self::location('beyond', 100.0, 100.0),
        ], 3);

        self::assertSame(['closest', 'second', 'mid'], self::names($result));
    }

    public function testRespectsLimitOfOne(): void
    {
        $finder = new NearestLocationsFinder(new EuclideanDistanceCalculator());

        $result = $finder->findNearest(new Coordinates(0.0, 0.0), [
            self::location('far', 5.0, 5.0),
            self::location('near', 1.0, 0.0),
        ], 1);

        self::assertSame(['near'], self::names($result));
    }

    #[DataProvider('invalidLimits')]
    public function testRejectsZeroOrNegativeLimit(int $invalidLimit): void
    {
        $finder = new NearestLocationsFinder(new EuclideanDistanceCalculator());

        $this->expectException(\InvalidArgumentException::class);

        $finder->findNearest(new Coordinates(0.0, 0.0), [], $invalidLimit);
    }

    public function testConsumesGeneratorForwardOnly(): void
    {
        $finder = new NearestLocationsFinder(new EuclideanDistanceCalculator());

        $generator = (static function (): \Generator {
            yield self::location('A', 1.0, 0.0);
            yield self::location('B', 0.0, 0.5);
        })();

        $result = $finder->findNearest(new Coordinates(0.0, 0.0), $generator, 3);

        self::assertSame(['B', 'A'], self::names($result));
    }

    public function testKeepsFirstInsertedOnTies(): void
    {
        $finder = new NearestLocationsFinder(new EuclideanDistanceCalculator());

        $result = $finder->findNearest(new Coordinates(0.0, 0.0), [
            self::location('First', 1.0, 0.0),
            self::location('Second', 0.0, 1.0),
            self::location('Third', -1.0, 0.0),
            self::location('Fourth', 0.0, -1.0),
        ], 3);

        self::assertSame(['First', 'Second', 'Third'], self::names($result));
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function invalidLimits(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-1];
        yield 'large negative' => [-100];
    }

    private static function location(string $name, float $x, float $y): NamedLocation
    {
        return new NamedLocation($name, new Coordinates($x, $y));
    }

    /**
     * @param list<LocationWithDistance> $locations
     *
     * @return list<string>
     */
    private static function names(array $locations): array
    {
        return array_map(
            static fn (LocationWithDistance $result): string => $result->location->name,
            $locations,
        );
    }
}
