<?php

namespace Gravity\Interfaces;

interface LoaderStrategyInterface
{
    /**
     * Load translations for a locale/domain from a resource.
     *
     * @param array<string,mixed>|string $resource The resource (file path, array, etc.)
     * @param string $locale The locale
     * @param string $domain The domain
     * @return array The translations ['key' => 'translated value']
     */
    public function load(array|string $resource, string $locale, string $domain = 'messages'): array;

    /**
     * Check if this loader can handle the given resource
     *
     * @param array<string, mixed>|string $resource
     * @return bool
     */
    public function supports(array|string $resource): bool;
}