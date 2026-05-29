<?php

declare(strict_types=1);

namespace App\Infrastructure\CoffeeShop;

use App\Application\CoffeeShop\CoffeeShopProviderInterface;
use App\Domain\Location\NamedLocation;
use App\Exception\CsvFetchException;
use Psr\Log\LoggerInterface;

final class RemoteCsvCoffeeShopProvider implements CoffeeShopProviderInterface
{
    public function __construct(
        private readonly HttpCsvFetcher $fetcher,
        private readonly CsvFileCache $cache,
        private readonly CsvLocationParser $parser,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return iterable<NamedLocation>
     */
    public function getAll(): iterable
    {
        if ($this->cache->has() && !$this->cache->isStale()) {
            return $this->parser->parse($this->cache->getPath());
        }

        try {
            $csv = $this->fetcher->fetch();
            $this->cache->set($csv);

            return $this->parser->parse($this->cache->getPath());
        } catch (CsvFetchException $e) {
            if ($this->cache->has()) {
                $this->logger->warning('Remote CSV fetch failed; using stale cache.', [
                    'exception' => $e->getMessage(),
                ]);

                return $this->parser->parse($this->cache->getPath());
            }

            throw $e;
        }
    }
}
