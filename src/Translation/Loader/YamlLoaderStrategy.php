<?php

namespace Gravity\Translation\Loader;

use Gravity\Interfaces\LoaderStrategyInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * YAML loader strategy
 * 
 * @requires symfony/yaml
 */
class YamlLoaderStrategy implements LoaderStrategyInterface
{
    public function supports(mixed $resource): bool
    {
        return is_string($resource) 
            && (str_ends_with($resource, '.yaml') || str_ends_with($resource, '.yml'));
    }

    public function load(mixed $resource, string $locale, string $domain = 'messages'): array
    {
        if (!class_exists(Yaml::class)) {
            throw new \RuntimeException(
                'YamlLoaderStrategy requires symfony/yaml. Install it with: composer require symfony/yaml'
            );
        }

        if (!is_string($resource)) {
            throw new \InvalidArgumentException('YamlLoaderStrategy expects a file path');
        }

        if (!file_exists($resource)) {
            throw new \InvalidArgumentException("Translation file not found: {$resource}");
        }

        $content = file_get_contents($resource);
        
        if ($content === false) {
            throw new \RuntimeException("Failed to read file: {$resource}");
        }

        /** @var array<string, string> */
        return Yaml::parse($content) ?? [];
    }
}