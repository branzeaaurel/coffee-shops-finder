<?php

declare(strict_types=1);

namespace App\Infrastructure\CoffeeShop;

use App\Domain\Location\Coordinates;
use App\Domain\Location\NamedLocation;
use App\Exception\NoValidLocationsException;
use Psr\Log\LoggerInterface;

class CsvLocationParser
{
    private const REASON_BLANK_ROW = 'blank_row';
    private const REASON_WRONG_COLUMN_COUNT = 'wrong_column_count';
    private const REASON_EMPTY_NAME = 'empty_name';
    private const REASON_NON_NUMERIC_X = 'non_numeric_x';
    private const REASON_NON_NUMERIC_Y = 'non_numeric_y';
    private const REASON_NON_FINITE_COORDINATES = 'non_finite_coordinates';

    /** @var list<string> */
    private const EXPECTED_HEADER = ['name', 'x', 'y'];

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return iterable<NamedLocation>
     */
    public function parse(string $filePath): iterable
    {
        $handle = @fopen($filePath, 'rb');
        if (false === $handle) {
            throw new \RuntimeException(sprintf('Cannot open CSV file: %s', $filePath));
        }

        try {
            $validCount = 0;
            $rowNumber = 0;

            $row = fgetcsv($handle);
            if (false !== $row && $this->isValidHeader($row)) {
                ++$rowNumber;
                $row = fgetcsv($handle);
            }

            while (false !== $row) {
                ++$rowNumber;

                $named = $this->parseRow($row, $rowNumber);
                if (null !== $named) {
                    ++$validCount;
                    yield $named;
                }

                $row = fgetcsv($handle);
            }

            if (0 === $validCount) {
                throw new NoValidLocationsException(sprintf('CSV file %s contains zero valid locations.', $filePath));
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param array<int, string|null> $header
     */
    private function isValidHeader(array $header): bool
    {
        if (3 !== \count($header)) {
            return false;
        }

        $normalized = array_map(
            static fn (?string $value): string => strtolower(trim((string) $value)),
            $header,
        );

        return self::EXPECTED_HEADER === $normalized;
    }

    /**
     * @param array<int, string|null> $row
     */
    private function parseRow(array $row, int $rowNumber): ?NamedLocation
    {
        if (1 === \count($row) && '' === trim((string) ($row[0] ?? ''))) {
            $this->logMalformedRow(self::REASON_BLANK_ROW, $rowNumber);

            return null;
        }

        if (3 !== \count($row)) {
            $this->logMalformedRow(self::REASON_WRONG_COLUMN_COUNT, $rowNumber, ['columns' => \count($row)]);

            return null;
        }

        $name = trim((string) ($row[0] ?? ''));
        $xRaw = trim((string) ($row[1] ?? ''));
        $yRaw = trim((string) ($row[2] ?? ''));

        if ('' === $name) {
            $this->logMalformedRow(self::REASON_EMPTY_NAME, $rowNumber);

            return null;
        }

        if (!is_numeric($xRaw)) {
            $this->logMalformedRow(self::REASON_NON_NUMERIC_X, $rowNumber, ['value' => $xRaw]);

            return null;
        }

        if (!is_numeric($yRaw)) {
            $this->logMalformedRow(self::REASON_NON_NUMERIC_Y, $rowNumber, ['value' => $yRaw]);

            return null;
        }

        try {
            return new NamedLocation($name, new Coordinates((float) $xRaw, (float) $yRaw));
        } catch (\InvalidArgumentException) {
            $this->logMalformedRow(self::REASON_NON_FINITE_COORDINATES, $rowNumber, ['x' => $xRaw, 'y' => $yRaw]);

            return null;
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    private function logMalformedRow(string $reason, int $rowNumber, array $context = []): void
    {
        $this->logger->warning('Malformed CSV row', [
            'reason' => $reason,
            'row' => $rowNumber,
            ...$context,
        ]);
    }
}
