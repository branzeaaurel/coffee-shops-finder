<?php

declare(strict_types=1);

namespace App\Infrastructure\CoffeeShop;

class CsvFileCache
{
    public function __construct(
        private readonly string $filePath,
        private readonly int $ttlSeconds = 300,
    ) {
        if ($ttlSeconds < 1) {
            throw new \InvalidArgumentException('TTL must be at least 1 second.');
        }
    }

    public function has(): bool
    {
        return is_file($this->filePath) && is_readable($this->filePath);
    }

    public function get(): string
    {
        $contents = @file_get_contents($this->filePath);
        if (false === $contents) {
            throw new \RuntimeException(sprintf('Cannot read cache file: %s', $this->filePath));
        }

        return $contents;
    }

    public function set(string $contents): void
    {
        $directory = \dirname($this->filePath);
        if (!is_dir($directory) && !@mkdir($directory, 0o775, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Cannot create cache directory: %s', $directory));
        }

        $temp = tempnam($directory, 'csv-cache-');
        if (false === $temp) {
            throw new \RuntimeException(sprintf('Cannot create temp file in: %s', $directory));
        }

        if (false === file_put_contents($temp, $contents)) {
            @unlink($temp);
            throw new \RuntimeException('Cannot write cache contents.');
        }

        if (!@rename($temp, $this->filePath)) {
            @unlink($temp);
            throw new \RuntimeException('Cannot rename cache file into place.');
        }
    }

    public function isStale(): bool
    {
        if (!$this->has()) {
            return true;
        }

        $mtime = @filemtime($this->filePath);
        if (false === $mtime) {
            return true;
        }

        return (time() - $mtime) >= $this->ttlSeconds;
    }

    public function getPath(): string
    {
        return $this->filePath;
    }
}
