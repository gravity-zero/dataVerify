<?php

namespace Gravity\Translation\Loader;

use Gravity\Interfaces\LoaderStrategyInterface;

class ArrayLoaderStrategy implements LoaderStrategyInterface
{
    public function supports(array|string $resource): bool
    {
        return is_array($resource);
    }

    /**
     * @return array<string,mixed>
     * @throws \InvalidArgumentException
     */
    public function load(array|string $resource, string $locale, string $domain = 'messages'): array
    {
        if (!is_array($resource)) {
            throw new \InvalidArgumentException('ArrayLoaderStrategy expects an array');
        }

        foreach ($resource as $key => $_) {
            if (!is_string($key)) {
                throw new \InvalidArgumentException('Translation keys must be strings (invalid key in array resource)');
            }
        }

        /** @var array<string, mixed> $resource */
        return $resource;
    }
}