<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Location;

use App\Domain\Location\Coordinates;
use App\Domain\Location\EuclideanDistanceCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EuclideanDistanceCalculatorTest extends TestCase
{
    public function testReturnsZeroForSamePoint(): void
    {
        $calculator = new EuclideanDistanceCalculator();

        self::assertSame(0.0, $calculator->calculate(
            new Coordinates(3.0, 4.0),
            new Coordinates(3.0, 4.0),
        ));
    }

    #[DataProvider('distancePairs')]
    public function testCalculatesEuclideanDistance(
        float $x1,
        float $y1,
        float $x2,
        float $y2,
        float $expected,
    ): void {
        $calculator = new EuclideanDistanceCalculator();

        self::assertEqualsWithDelta(
            $expected,
            $calculator->calculate(new Coordinates($x1, $y1), new Coordinates($x2, $y2)),
            1e-9,
        );
    }

    public function testIsSymmetric(): void
    {
        $calculator = new EuclideanDistanceCalculator();
        $a = new Coordinates(1.5, -2.5);
        $b = new Coordinates(4.0, 7.25);

        self::assertSame(
            $calculator->calculate($a, $b),
            $calculator->calculate($b, $a),
        );
    }

    /**
     * @return iterable<string, array{float, float, float, float, float}>
     */
    public static function distancePairs(): iterable
    {
        yield '3-4-5 triangle' => [0.0, 0.0, 3.0, 4.0, 5.0];
        yield 'horizontal segment' => [0.0, 0.0, 5.0, 0.0, 5.0];
        yield 'vertical segment' => [0.0, 0.0, 0.0, 7.0, 7.0];
        yield 'negative offset' => [-1.0, -1.0, 2.0, 3.0, 5.0];
    }
}
