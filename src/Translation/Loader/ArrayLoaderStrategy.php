<?php

namespace Gravity\Translation\Loader;

use Gravity\Interfaces\LoaderStrategyInterface;

class ArrayLoaderStrategy implements LoaderStrategyInterface
{
    public function supports(mixed $resource): bool
    {
        return is_array($resource);
    }

    public function load(mixed $resource, string $locale, string $domain = 'messages'): array
    {
        if (!is_array($resource)) {
            throw new \InvalidArgumentException('ArrayLoaderStrategy expects an array');
        }

        return $resource;
    }
}