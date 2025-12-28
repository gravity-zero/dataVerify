<?php

namespace Gravity\Interfaces;

interface LoaderStrategyInterface
{
    /**
     * Load translations from a resource
     *
     * @param mixed $resource The resource (file path, array, etc.)
     * @param string $locale The locale
     * @param string $domain The domain
     * @return array The translations ['key' => 'translated value']
     */
    public function load(array|string $resource, string $locale, string $domain = 'messages'): array;

    /**
     * Check if this loader can handle the given resource
     *
     * @param mixed $resource
     * @return bool
     */
    public function supports(array|string $resource): bool;
}