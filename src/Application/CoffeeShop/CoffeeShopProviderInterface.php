<?php

declare(strict_types=1);

namespace App\Application\CoffeeShop;

use App\Domain\Location\NamedLocation;

interface CoffeeShopProviderInterface
{
    /**
     * @return iterable<NamedLocation>
     */
    public function getAll(): iterable;
}
