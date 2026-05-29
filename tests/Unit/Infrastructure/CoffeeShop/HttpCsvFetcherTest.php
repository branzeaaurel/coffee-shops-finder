<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\CoffeeShop;

use App\Exception\CsvFetchException;
use App\Infrastructure\CoffeeShop\HttpCsvFetcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class HttpCsvFetcherTest extends TestCase
{
    private const CSV_URL = 'https://example.com/coffee.csv';
    private const CSV_CONTENT = "Name,X,Y\nA,1,2";
    private const DEFAULT_TIMEOUT = 5;
    private const FAILURE_BODY = 'boom';
    private const SERVER_ERROR_CODE = 500;

    public function testFetchesContentOnSuccess(): void
    {
        $client = new MockHttpClient([
            new MockResponse(self::CSV_CONTENT),
        ]);

        $fetcher = new HttpCsvFetcher($client, self::CSV_URL, self::DEFAULT_TIMEOUT);

        self::assertSame(self::CSV_CONTENT, $fetcher->fetch());
    }

    public function testRetriesAfterFailureAndSucceedsOnSecondAttempt(): void
    {
        $client = new MockHttpClient([
            new MockResponse(self::FAILURE_BODY, ['http_code' => self::SERVER_ERROR_CODE]),
            new MockResponse(self::CSV_CONTENT),
        ]);

        $fetcher = new HttpCsvFetcher($client, self::CSV_URL, self::DEFAULT_TIMEOUT);

        self::assertSame(self::CSV_CONTENT, $fetcher->fetch());
    }

    public function testThrowsCsvFetchExceptionAfterAllAttemptsFail(): void
    {
        $client = new MockHttpClient([
            new MockResponse(self::FAILURE_BODY, ['http_code' => self::SERVER_ERROR_CODE]),
            new MockResponse(self::FAILURE_BODY, ['http_code' => self::SERVER_ERROR_CODE]),
            new MockResponse(self::FAILURE_BODY, ['http_code' => self::SERVER_ERROR_CODE]),
        ]);

        $fetcher = new HttpCsvFetcher($client, self::CSV_URL, self::DEFAULT_TIMEOUT);

        $this->expectException(CsvFetchException::class);

        $fetcher->fetch();
    }

    public function testPassesTimeoutToHttpClient(): void
    {
        $timeoutSeconds = 7;

        /** @var list<array<string, mixed>> $capturedOptions */
        $capturedOptions = [];

        $client = new MockHttpClient(
            function (string $method, string $url, array $options) use (&$capturedOptions): MockResponse {
                $capturedOptions[] = $options;

                return new MockResponse('ok');
            },
        );

        $fetcher = new HttpCsvFetcher($client, self::CSV_URL, $timeoutSeconds);
        $fetcher->fetch();

        self::assertCount(1, $capturedOptions);
        self::assertArrayHasKey('timeout', $capturedOptions[0]);
        self::assertEqualsWithDelta($timeoutSeconds, $capturedOptions[0]['timeout'], 0.001);
    }
}
