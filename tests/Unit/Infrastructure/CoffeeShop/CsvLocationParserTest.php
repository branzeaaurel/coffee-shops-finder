<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\CoffeeShop;

use App\Domain\Location\NamedLocation;
use App\Exception\NoValidLocationsException;
use App\Infrastructure\CoffeeShop\CsvLocationParser;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class CsvLocationParserTest extends TestCase
{
    private const FIXTURES = __DIR__.'/../../../Fixtures/Csv';

    public function testParsesValidCsv(): void
    {
        $parser = new CsvLocationParser(new NullLogger());

        $result = iterator_to_array($parser->parse(self::FIXTURES.'/valid.csv'), false);

        self::assertSame(['Starbucks Seattle', 'Starbucks SF', 'Coffee Origin'], self::names($result));
        self::assertSame(47.6, $result[0]->coordinates->x);
        self::assertSame(-122.4, $result[0]->coordinates->y);
    }

    public function testTrimsAllFields(): void
    {
        $parser = new CsvLocationParser(new NullLogger());

        $result = iterator_to_array($parser->parse(self::FIXTURES.'/with_whitespace.csv'), false);

        self::assertCount(1, $result);
        self::assertSame('Starbucks Padded', $result[0]->name);
        self::assertSame(47.6, $result[0]->coordinates->x);
        self::assertSame(-122.4, $result[0]->coordinates->y);
    }

    public function testSkipsMalformedRowsAndYieldsOnlyValid(): void
    {
        $parser = new CsvLocationParser(new NullLogger());

        $result = iterator_to_array($parser->parse(self::FIXTURES.'/malformed_rows.csv'), false);

        self::assertSame(['Valid First', 'Valid Last'], self::names($result));
    }

    public function testLogsStructuredReasonCodesForMalformedRows(): void
    {
        $handler = new TestHandler();
        $parser = new CsvLocationParser(new Logger('test', [$handler]));

        iterator_to_array($parser->parse(self::FIXTURES.'/malformed_rows.csv'), false);

        $reasons = array_map(
            static fn (LogRecord $record): string => (string) ($record->context['reason'] ?? ''),
            $handler->getRecords(),
        );

        self::assertSame(
            ['blank_row', 'empty_name', 'non_numeric_x', 'non_finite_coordinates', 'non_numeric_y', 'wrong_column_count'],
            $reasons,
        );
    }

    public function testThrowsOnEmptyFile(): void
    {
        $parser = new CsvLocationParser(new NullLogger());

        $this->expectException(NoValidLocationsException::class);

        iterator_to_array($parser->parse(self::FIXTURES.'/empty.csv'), false);
    }

    public function testThrowsOnInvalidHeader(): void
    {
        $parser = new CsvLocationParser(new NullLogger());

        $this->expectException(NoValidLocationsException::class);

        iterator_to_array($parser->parse(self::FIXTURES.'/invalid_header.csv'), false);
    }

    public function testThrowsOnHeaderOnly(): void
    {
        $parser = new CsvLocationParser(new NullLogger());

        $this->expectException(NoValidLocationsException::class);

        iterator_to_array($parser->parse(self::FIXTURES.'/header_only.csv'), false);
    }

    public function testThrowsOnZeroValidRows(): void
    {
        $parser = new CsvLocationParser(new NullLogger());

        $this->expectException(NoValidLocationsException::class);

        iterator_to_array($parser->parse(self::FIXTURES.'/zero_valid.csv'), false);
    }

    /**
     * @param list<NamedLocation> $locations
     *
     * @return list<string>
     */
    private static function names(array $locations): array
    {
        return array_map(
            static fn (NamedLocation $location): string => $location->name,
            $locations,
        );
    }
}
