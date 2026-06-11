<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Application\CoffeeShop\CoffeeShopProviderInterface;
use App\Domain\Location\Coordinates;
use App\Domain\Location\NamedLocation;
use App\Exception\CsvFetchException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

final class GraphQlControllerTest extends KernelTestCase
{
    private const ENDPOINT = '/api/graphql';

    public function testReturnsThreeNearestShopsInCorrectShape(): void
    {
        $kernel = self::bootKernel();
        $this->replaceProviderLocations([
            self::location('Closest', 0.1, 0.5),
            self::location('Second', 0.5, 0.5),
            self::location('Third', 1.0, 1.0),
            self::location('Far', 10.0, 10.0),
            self::location('Beyond', 100.0, 100.0),
        ]);

        $response = $this->graphql(
            $kernel,
            'query($x: Float!, $y: Float!) {
                nearestCoffeeShops(x: $x, y: $y) {
                    name location { x y } distance
                }
            }',
            ['x' => 0.0, 'y' => 0.0],
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $shops = self::json($response)['data']['nearestCoffeeShops'];

        self::assertCount(3, $shops);
        self::assertSame('Closest', $shops[0]['name']);
        self::assertSame('Second', $shops[1]['name']);
        self::assertSame('Third', $shops[2]['name']);
        self::assertSame(['x' => 0.1, 'y' => 0.5], $shops[0]['location']);
        self::assertSame(0.5099, $shops[0]['distance']);
    }

    public function testDistanceIsRoundedToFourDecimalPlaces(): void
    {
        $kernel = self::bootKernel();
        $this->replaceProviderLocations([
            self::location('Diagonal', 1.0, 1.0),
        ]);

        $response = $this->graphql(
            $kernel,
            '{ nearestCoffeeShops(x: 0.0, y: 0.0) { distance } }',
        );

        self::assertSame(1.4142, self::json($response)['data']['nearestCoffeeShops'][0]['distance']);
    }

    public function testMissingRequiredArgumentReturnsGraphQlError(): void
    {
        $kernel = self::bootKernel();

        $response = $this->graphql($kernel, '{ nearestCoffeeShops(x: 0.0) { name } }');

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = self::json($response);
        self::assertArrayHasKey('errors', $data);
        self::assertArrayNotHasKey('data', $data);
    }

    public function testCsvFetchExceptionIsMappedToCoffeeShopsUnavailable(): void
    {
        $kernel = self::bootKernel();
        $this->failProviderWith(new CsvFetchException('upstream down'));

        $response = $this->graphql($kernel, '{ nearestCoffeeShops(x: 0.0, y: 0.0) { name } }');

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame([
            'errors' => [
                [
                    'message' => 'Coffee shop data is temporarily unavailable. Please try again later.',
                    'extensions' => ['code' => 'COFFEE_SHOPS_UNAVAILABLE'],
                ],
            ],
        ], self::json($response));
    }

    public function testUnexpectedThrowableIsMaskedAsInternalError(): void
    {
        $kernel = self::bootKernel();
        $this->failProviderWith(new \RuntimeException('Private internal detail.'));

        $response = $this->graphql($kernel, '{ nearestCoffeeShops(x: 0.0, y: 0.0) { name } }');

        $data = self::json($response);
        self::assertSame(
            'An unexpected error occurred. Please try again later.',
            $data['errors'][0]['message'],
        );
        self::assertSame('INTERNAL_ERROR', $data['errors'][0]['extensions']['code']);
        self::assertStringNotContainsString('Private internal detail.', (string) $response->getContent());
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function graphql(KernelInterface $kernel, string $query, array $variables = []): Response
    {
        return $kernel->handle(Request::create(
            self::ENDPOINT,
            Request::METHOD_POST,
            content: (string) json_encode(['query' => $query, 'variables' => $variables]),
        ));
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
