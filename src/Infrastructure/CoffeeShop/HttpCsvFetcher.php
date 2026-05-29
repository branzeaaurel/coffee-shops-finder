<?php

declare(strict_types=1);

namespace App\Infrastructure\CoffeeShop;

use App\Exception\CsvFetchException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HttpCsvFetcher
{
    private const MAX_RETRIES = 2;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $url,
        private readonly int $timeoutSeconds = 5,
    ) {
        if ($timeoutSeconds < 1) {
            throw new \InvalidArgumentException('Timeout must be at least 1 second.');
        }
        if ('' === trim($url)) {
            throw new \InvalidArgumentException('URL cannot be empty.');
        }
    }

    public function fetch(): string
    {
        $attempts = self::MAX_RETRIES + 1;
        $lastError = null;

        for ($attempt = 1; $attempt <= $attempts; ++$attempt) {
            try {
                $response = $this->httpClient->request('GET', $this->url, [
                    'timeout' => $this->timeoutSeconds,
                ]);

                return $response->getContent();
            } catch (ExceptionInterface $e) {
                $lastError = $e;
            }
        }

        throw new CsvFetchException(sprintf('Failed to fetch CSV from %s after %d attempts.', $this->url, $attempts), 0, $lastError);
    }
}
