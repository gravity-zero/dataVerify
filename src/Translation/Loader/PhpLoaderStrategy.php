<?php

namespace Gravity\Translation\Loader;

use Gravity\Interfaces\LoaderStrategyInterface;

class PhpLoaderStrategy implements LoaderStrategyInterface
{
    public function supports(array|string $resource): bool
    {
        return is_string($resource) && str_ends_with($resource, '.php');
    }

    /**
     * @return array<string,mixed>
     * @throws \InvalidArgumentException
     */
    public function load(array|string $resource, string $locale, string $domain = 'messages'): array
    {
        if (!is_string($resource)) {
            throw new \InvalidArgumentException('PhpLoaderStrategy expects a file path');
        }

        if (!file_exists($resource)) {
            throw new \InvalidArgumentException("Translation file not found: {$resource}");
        }

        /** @var mixed $messages */
        $messages = require $resource;

        if (!is_array($messages)) {
            throw new \InvalidArgumentException("Translation file must return an array: {$resource}");
        }

        foreach ($messages as $key => $_) {
            if (!is_string($key)) {
                throw new \InvalidArgumentException("Translation keys must be strings (invalid key in {$resource})");
            }
        }

        /** @var array<string,mixed> $messages */
        return $messages;
    }
}