<?php

namespace Gravity\Engine;

/**
 * DataTraverser
 * 
 * Responsible for navigating and accessing data from arrays or objects.
 * Handles dot notation, nested paths, and value retrieval.
 */
class DataTraverser
{
    public function __construct(
        private array|object $data
    ) {}

    /**
     * Get value from root level field
     */
    public function getValue(string $fieldName): mixed
    {
        return is_object($this->data) 
            ? ($this->data->{$fieldName} ?? null)
            : ($this->data[$fieldName] ?? null);
    }

    /**
     * Get value from field with dot notation support
     * Examples: 'user.type', 'address.city'
     */
    public function getFieldValue(string $field): mixed
    {
        if (!str_contains($field, '.')) {
            return $this->getValue($field);
        }
        
        $parts = explode('.', $field);
        $rootField = array_shift($parts);
        $value = $this->getValue($rootField);
        
        return $this->traversePath($value, $parts);
    }

    /**
     * Traverse nested path in data structure
     */
    public function traversePath(mixed $data, array $path): mixed
    {
        foreach ($path as $segment) {
            if (is_object($data)) {
                $data = $data->{$segment} ?? null;
            } elseif (is_array($data)) {
                $data = $data[$segment] ?? null;
            } else {
                return null;
            }
        }
        return $data;
    }

    /**
     * Check if value is considered empty for validation purposes
     * Note: Booleans are never empty, numeric strings (including "0") are not empty
     */
    public function isValueEmpty(mixed $value): bool
    {
        if (is_bool($value)) {
            return false;
        }
        return empty($value) && !is_numeric($value);
    }
}
