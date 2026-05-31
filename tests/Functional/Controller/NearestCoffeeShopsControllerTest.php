<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Application\CoffeeShop\CoffeeShopProviderInterface;
use App\Domain\Location\Coordinates;
use App\Domain\Location\NamedLocation;
use App\Exception\CsvFetchException;
use App\Exception\NoValidLocationsException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class NearestCoffeeShopsControllerTest extends KernelTestCase
{
    private const ENDPOINT = '/api/coffee-shops/nearest';

    public function testValidRequestReturnsThreeResultsInCorrectShape(): void
    {
        $kernel = self::bootKernel();
        $this->replaceProviderLocations([
            self::location('Closest', 0.1, 0.5),
            self::location('Second', 0.5, 0.5),
            self::location('Third', 1.0, 1.0),
            self::location('Far', 10.0, 10.0),
            self::location('Beyond', 100.0, 100.0),
        ]);

        $response = $kernel->handle(Request::create(self::ENDPOINT.'?x=0.0&y=0.0'));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = self::json($response);

        self::assertCount(3, $data);
        self::assertSame('Closest', $data[0]['name']);
        self::assertSame('Second', $data[1]['name']);
        self::assertSame('Third', $data[2]['name']);
        self::assertSame(['x' => 0.1, 'y' => 0.5], $data[0]['location']);
        self::assertSame(0.5099, $data[0]['distance']);
    }

    public function testDistancesAreRoundedToFourDecimalPlaces(): void
    {
        $kernel = self::bootKernel();
        $this->replaceProviderLocations([
            self::location('Diagonal', 1.0, 1.0),
        ]);

        $response = $kernel->handle(Request::create(self::ENDPOINT.'?x=0.0&y=0.0'));
        $data = self::json($response);

        self::assertSame(1.4142, $data[0]['distance']);
    }

    public function testResponseContainsLocationWithXAndY(): void
    {
        $kernel = self::bootKernel();
        $this->replaceProviderLocations([
            self::location('Asymmetric', 3.5, -2.5),
        ]);

        $response = $kernel->handle(Request::create(self::ENDPOINT.'?x=0.0&y=0.0'));
        $data = self::json($response);

        self::assertSame(3.5, $data[0]['location']['x']);
        self::assertSame(-2.5, $data[0]['location']['y']);
    }

    public function testMissingXReturnsBadRequest(): void
    {
        $kernel = self::bootKernel();
        $response = $kernel->handle(Request::create(self::ENDPOINT.'?y=0'));

        $this->assertError(
            $response,
            Response::HTTP_BAD_REQUEST,
            'INVALID_COORDINATES',
            'Missing required query parameter "x".',
        );
    }

    public function testMissingYReturnsBadRequest(): void
    {
        $kernel = self::bootKernel();
        $response = $kernel->handle(Request::create(self::ENDPOINT.'?x=0'));

        $this->assertError(
            $response,
            Response::HTTP_BAD_REQUEST,
            'INVALID_COORDINATES',
            'Missing required query parameter "y".',
        );
    }

    public function testNonNumericXReturnsBadRequest(): void
    {
        $kernel = self::bootKernel();
        $response = $kernel->handle(Request::create(self::ENDPOINT.'?x=abc&y=0'));

        $this->assertError(
            $response,
            Response::HTTP_BAD_REQUEST,
            'INVALID_COORDINATES',
            'Query parameter "x" must be a finite number.',
        );
    }

    #[DataProvider('nonFiniteXValues')]
    public function testNonFiniteXReturnsBadRequest(string $value): void
    {
        $kernel = self::bootKernel();
        $response = $kernel->handle(Request::create(self::ENDPOINT.'?x='.$value.'&y=0'));

        $this->assertError(
            $response,
            Response::HTTP_BAD_REQUEST,
            'INVALID_COORDINATES',
            'Query parameter "x" must be a finite number.',
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function nonFiniteXValues(): iterable
    {
        yield 'NAN' => ['NAN'];
        yield '1e309' => ['1e309'];
    }

    public function testArrayXReturnsBadRequest(): void
    {
        $kernel = self::bootKernel();

        $response = $kernel->handle(Request::create(self::ENDPOINT.'?x[]=1&y=0'));

        $this->assertError(
            $response,
            Response::HTTP_BAD_REQUEST,
            'INVALID_COORDINATES',
            'Query parameter "x" must be a finite number.',
        );
    }

    public function testCsvFetchExceptionReturns503(): void
    {
        $kernel = self::bootKernel();
        $this->failProviderWith(new CsvFetchException('upstream down'));

        $response = $kernel->handle(Request::create(self::ENDPOINT.'?x=0&y=0'));

        $this->assertError(
            $response,
            Response::HTTP_SERVICE_UNAVAILABLE,
            'COFFEE_SHOPS_UNAVAILABLE',
            'Coffee shop data is temporarily unavailable. Please try again later.',
        );
    }

    public function testNoValidLocationsExceptionReturns422(): void
    {
        $kernel = self::bootKernel();
        $this->failProviderWith(new NoValidLocationsException('empty'));

        $response = $kernel->handle(Request::create(self::ENDPOINT.'?x=0&y=0'));

        $this->assertError(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'NO_VALID_LOCATIONS',
            'No valid coffee shop locations are available.',
        );
    }

    public function testUnexpectedThrowableReturns500WithGenericMessage(): void
    {
        $kernel = self::bootKernel();
        $this->failProviderWith(new \RuntimeException('Private internal detail.'));

        $response = $kernel->handle(Request::create(self::ENDPOINT.'?x=0&y=0'));

        $this->assertError(
            $response,
            Response::HTTP_INTERNAL_SERVER_ERROR,
            'INTERNAL_ERROR',
            'An unexpected error occurred. Please try again later.',
        );

        self::assertStringNotContainsString('Private internal detail.', (string) $response->getContent());
    }

    /**
     * @param iterable<NamedLocation> $locations
     */
    private function replaceProviderLocations(iterable $locations): void
    {
        $provider = $this->createMock(CoffeeShopProviderInterface::class);
        $provider->method('getAll')->willReturn($locations);
        self::getContainer()->set(CoffeeShopProviderInterface::class, $provider);
    }

    private function failProviderWith(\Throwable $exception): void
    {
        $provider = $this->createMock(CoffeeShopProviderInterface::class);
        $provider->method('getAll')->willThrowException($exception);
        self::getContainer()->set(CoffeeShopProviderInterface::class, $provider);
    }

    private function assertError(Response $response, int $status, string $code, string $message): void
    {
        self::assertSame($status, $response->getStatusCode());
        self::assertSame(
            ['error' => ['code' => $code, 'message' => $message]],
            self::json($response),
        );
    }

    /**
     * @return array<mixed>
     */
    private static function json(Response $response): array
    {
        $data = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($data);

        return $data;
    }

    private static function location(string $name, float $x, float $y): NamedLocation
    {
        return new NamedLocation($name, new Coordinates($x, $y));
    }
}
