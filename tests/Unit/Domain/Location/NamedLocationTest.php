<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Location;

use App\Domain\Location\Coordinates;
use App\Domain\Location\NamedLocation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NamedLocationTest extends TestCase
{
    public function testHoldsNameAndCoordinates(): void
    {
        $coordinates = new Coordinates(1.0, 2.0);
        $location = new NamedLocation('Starbucks Seattle', $coordinates);

        self::assertSame('Starbucks Seattle', $location->name);
        self::assertSame($coordinates, $location->coordinates);
    }

    public function testItStoresOriginalNameUnchanged(): void
    {
        $location = new NamedLocation('  Central Cafe  ', new Coordinates(10.0, 20.0));

        self::assertSame('  Central Cafe  ', $location->name);
    }

    #[DataProvider('emptyNames')]
    public function testRejectsEmptyOrWhitespaceName(string $invalidName): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new NamedLocation($invalidName, new Coordinates(0.0, 0.0));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function emptyNames(): iterable
    {
        yield 'empty string' => [''];
        yield 'spaces only' => ['   '];
        yield 'tabs only' => ["\t\t"];
        yield 'newline only' => ["\n"];
    }
}
