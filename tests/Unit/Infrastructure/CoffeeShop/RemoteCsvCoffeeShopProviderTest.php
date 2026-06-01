<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\CoffeeShop;

use App\Domain\Location\Coordinates;
use App\Domain\Location\NamedLocation;
use App\Exception\CsvFetchException;
use App\Infrastructure\CoffeeShop\CsvFileCache;
use App\Infrastructure\CoffeeShop\CsvLocationParser;
use App\Infrastructure\CoffeeShop\HttpCsvFetcher;
use App\Infrastructure\CoffeeShop\RemoteCsvCoffeeShopProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class RemoteCsvCoffeeShopProviderTest extends TestCase
{
    private const CSV_CONTENT = 'Name,X,Y';

    public function testUsesFreshCacheDirectlyWithoutFetching(): void
    {
        $fetcher = $this->createMock(HttpCsvFetcher::class);
        $cache = $this->createMock(CsvFileCache::class);
        $parser = $this->createMock(CsvLocationParser::class);

        $cache->method('has')->willReturn(true);
        $cache->method('isStale')->willReturn(false);
        $cache->method('getPath')->willReturn('/tmp/fresh.csv');

        $fetcher->expects(self::never())->method('fetch');
        $cache->expects(self::never())->method('set');
        $parser->expects(self::once())->method('parse')->with('/tmp/fresh.csv')
            ->willReturn(self::oneLocation('Fresh'));

        $provider = new RemoteCsvCoffeeShopProvider($fetcher, $cache, $parser, new NullLogger());

        self::assertSame(['Fresh'], self::namesFrom($provider->getAll()));
    }

    public function testFetchesAndUpdatesCacheWhenStale(): void
    {
        $fetcher = $this->createMock(HttpCsvFetcher::class);
        $cache = $this->createMock(CsvFileCache::class);
        $parser = $this->createMock(CsvLocationParser::class);

        $cache->method('has')->willReturn(true);
        $cache->method('isStale')->willReturn(true);
        $cache->method('getPath')->willReturn('/tmp/refreshed.csv');

        $fetcher->expects(self::once())->method('fetch')->willReturn(self::CSV_CONTENT);
        $cache->expects(self::once())->method('set')->with(self::CSV_CONTENT);
        $parser->expects(self::once())->method('parse')->with('/tmp/refreshed.csv')
            ->willReturn(self::oneLocation('Refreshed'));

        $provider = new RemoteCsvCoffeeShopProvider($fetcher, $cache, $parser, new NullLogger());

        self::assertSame(['Refreshed'], self::namesFrom($provider->getAll()));
    }

    public function testFetchesWhenCacheMissing(): void
    {
        $fetcher = $this->createMock(HttpCsvFetcher::class);
        $cache = $this->createMock(CsvFileCache::class);
        $parser = $this->createMock(CsvLocationParser::class);

        $cache->method('has')->willReturn(false);
        $cache->method('isStale')->willReturn(true);
        $cache->method('getPath')->willReturn('/tmp/new.csv');

        $fetcher->expects(self::once())->method('fetch')->willReturn(self::CSV_CONTENT);
        $cache->expects(self::once())->method('set')->with(self::CSV_CONTENT);
        $parser->expects(self::once())->method('parse')->with('/tmp/new.csv')
            ->willReturn(self::oneLocation('New'));

        $provider = new RemoteCsvCoffeeShopProvider($fetcher, $cache, $parser, new NullLogger());

        self::assertSame(['New'], self::namesFrom($provider->getAll()));
    }

    public function testUsesStaleCacheAsFallbackWhenFetchFails(): void
    {
        $fetcher = $this->createMock(HttpCsvFetcher::class);
        $cache = $this->createMock(CsvFileCache::class);
        $parser = $this->createMock(CsvLocationParser::class);
        $logger = $this->createMock(LoggerInterface::class);

        $cache->method('has')->willReturn(true);
        $cache->method('isStale')->willReturn(true);
        $cache->method('getPath')->willReturn('/tmp/stale.csv');

        $fetcher->method('fetch')->willThrowException(new CsvFetchException('upstream down'));
        $cache->expects(self::never())->method('set');
        $logger->expects(self::once())->method('warning');
        $parser->expects(self::once())->method('parse')->with('/tmp/stale.csv')
            ->willReturn(self::oneLocation('Stale'));

        $provider = new RemoteCsvCoffeeShopProvider($fetcher, $cache, $parser, $logger);

        self::assertSame(['Stale'], self::namesFrom($provider->getAll()));
    }

    public function testThrowsWhenFetchFailsAndNoCacheExists(): void
    {
        $fetcher = $this->createMock(HttpCsvFetcher::class);
        $cache = $this->createMock(CsvFileCache::class);
        $parser = $this->createMock(CsvLocationParser::class);

        $cache->method('has')->willReturn(false);
        $cache->method('isStale')->willReturn(true);

        $fetcher->method('fetch')->willThrowException(new CsvFetchException('upstream down'));
        $parser->expects(self::never())->method('parse');

        $provider = new RemoteCsvCoffeeShopProvider($fetcher, $cache, $parser, new NullLogger());

        $this->expectException(CsvFetchException::class);

        iterator_to_array($provider->getAll(), false);
    }

    /**
     * @return \Generator<int, NamedLocation>
     */
    private static function oneLocation(string $name): \Generator
    {
        yield new NamedLocation($name, new Coordinates(0.0, 0.0));
    }

    /**
     * @param iterable<NamedLocation> $locations
     *
     * @return list<string>
     */
    private static function namesFrom(iterable $locations): array
    {
        $names = [];
        foreach ($locations as $location) {
            $names[] = $location->name;
        }

        return $names;
    }
}
