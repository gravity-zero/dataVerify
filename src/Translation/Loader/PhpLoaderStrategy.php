<?php

namespace Gravity\Translation\Loader;

use Gravity\Interfaces\LoaderStrategyInterface;

class PhpLoaderStrategy implements LoaderStrategyInterface
{
    public function supports(mixed $resource): bool
    {
        if (!is_string($resource)) {
            return false;
        }
        
        return str_ends_with($resource, '.php');
    }

    public function load(mixed $resource, string $locale, string $domain = 'messages'): array
    {
        if (!is_string($resource)) {
            throw new \InvalidArgumentException('PhpLoaderStrategy expects a file path');
        }

        if (!file_exists($resource)) {
            throw new \InvalidArgumentException("Translation file not found: {$resource}");
        }

        $messages = require $resource;

        if (!is_array($messages)) {
            throw new \InvalidArgumentException("Translation file must return an array: {$resource}");
        }

        return $messages;
    }
}