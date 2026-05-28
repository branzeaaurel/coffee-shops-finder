<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Location;

use App\Domain\Location\Coordinates;
use App\Domain\Location\LocationWithDistance;
use App\Domain\Location\NamedLocation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LocationWithDistanceTest extends TestCase
{
    public function testHoldsLocationAndDistance(): void
    {
        $named = new NamedLocation('Some place', new Coordinates(1.0, 2.0));
        $result = new LocationWithDistance($named, 4.5);

        self::assertSame($named, $result->location);
        self::assertSame(4.5, $result->distance);
    }

    public function testAllowsZeroDistance(): void
    {
        $named = new NamedLocation('Origin', new Coordinates(0.0, 0.0));
        $result = new LocationWithDistance($named, 0.0);

        self::assertSame(0.0, $result->distance);
    }

    #[DataProvider('invalidDistances')]
    public function testRejectsInvalidDistance(float $invalid): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new LocationWithDistance(
            new NamedLocation('Some place', new Coordinates(0.0, 0.0)),
            $invalid,
        );
    }

    /**
     * @return iterable<string, array{float}>
     */
    public static function invalidDistances(): iterable
    {
        yield 'negative' => [-0.1];
        yield 'NAN' => [\NAN];
        yield 'INF' => [\INF];
        yield '-INF' => [-\INF];
    }
}
