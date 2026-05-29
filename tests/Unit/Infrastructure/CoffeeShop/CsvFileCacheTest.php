<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\CoffeeShop;

use App\Infrastructure\CoffeeShop\CsvFileCache;
use PHPUnit\Framework\TestCase;

final class CsvFileCacheTest extends TestCase
{
    private const CACHE_TTL = 60;

    private string $tmpDir;
    private string $cachePath;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().'/coffee-shops-cache-'.uniqid('', true);
        mkdir($this->tmpDir, 0o775, true);
        $this->cachePath = $this->tmpDir.'/coffee_shops.csv';
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function testHasReturnsFalseWhenFileMissing(): void
    {
        $cache = new CsvFileCache($this->cachePath, self::CACHE_TTL);

        self::assertFalse($cache->has());
    }

    public function testHasReturnsTrueAfterSet(): void
    {
        $cache = new CsvFileCache($this->cachePath, self::CACHE_TTL);
        $cache->set('hello');

        self::assertTrue($cache->has());
    }

    public function testGetReturnsContentsAfterSet(): void
    {
        $cache = new CsvFileCache($this->cachePath, self::CACHE_TTL);
        $cache->set("Name,X,Y\nA,1,2");

        self::assertSame("Name,X,Y\nA,1,2", $cache->get());
    }

    public function testSetOverwritesExistingContents(): void
    {
        $cache = new CsvFileCache($this->cachePath, self::CACHE_TTL);
        $cache->set('first');
        $cache->set('second');

        self::assertSame('second', $cache->get());
    }

    public function testIsStaleReturnsTrueWhenFileMissing(): void
    {
        $cache = new CsvFileCache($this->cachePath, self::CACHE_TTL);

        self::assertTrue($cache->isStale());
    }

    public function testIsStaleReturnsFalseImmediatelyAfterSet(): void
    {
        $cache = new CsvFileCache($this->cachePath, self::CACHE_TTL);
        $cache->set('fresh');

        self::assertFalse($cache->isStale());
    }

    public function testIsStaleReturnsTrueWhenOlderThanTtl(): void
    {
        $cache = new CsvFileCache($this->cachePath, self::CACHE_TTL);
        $cache->set('old');
        touch($this->cachePath, time() - 120);

        self::assertTrue($cache->isStale());
    }

    public function testSetCreatesMissingDirectory(): void
    {
        $nestedPath = $this->tmpDir.'/nested/dir/cache.csv';
        $cache = new CsvFileCache($nestedPath, self::CACHE_TTL);

        $cache->set('data');

        self::assertFileExists($nestedPath);
    }

    public function testGetPathReturnsConfiguredPath(): void
    {
        $cache = new CsvFileCache($this->cachePath, self::CACHE_TTL);

        self::assertSame($this->cachePath, $cache->getPath());
    }

    public function testGetThrowsWhenFileMissing(): void
    {
        $cache = new CsvFileCache($this->cachePath, self::CACHE_TTL);

        $this->expectException(\RuntimeException::class);

        $cache->get();
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $entries = scandir($dir);
        if (false === $entries) {
            return;
        }

        foreach ($entries as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }

            $path = $dir.'/'.$entry;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
