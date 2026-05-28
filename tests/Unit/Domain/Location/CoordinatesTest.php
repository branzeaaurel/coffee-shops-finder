<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Location;

use App\Domain\Location\Coordinates;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CoordinatesTest extends TestCase
{
    public function testStoresXAndY(): void
    {
        $coordinates = new Coordinates(47.6, -122.4);

        self::assertSame(47.6, $coordinates->x);
        self::assertSame(-122.4, $coordinates->y);
    }

    #[DataProvider('nonFiniteValues')]
    public function testRejectsNonFiniteX(float $invalidX): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Coordinates($invalidX, 0.0);
    }

    #[DataProvider('nonFiniteValues')]
    public function testRejectsNonFiniteY(float $invalidY): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Coordinates(0.0, $invalidY);
    }

    /**
     * @return iterable<string, array{float}>
     */
    public static function nonFiniteValues(): iterable
    {
        yield 'NAN' => [\NAN];
        yield 'INF' => [\INF];
        yield '-INF' => [-\INF];
    }
}
