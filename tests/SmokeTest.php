<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;

final class SmokeTest extends TestCase
{
    public function testBootstrapWorks(): void
    {
        self::assertContains('curl', get_loaded_extensions());
    }
}
