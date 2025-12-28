<?php

namespace Gravity\Registry;

/**
 * Registry for all validation rules
 * 
 * Stores validations with their metadata and provides fast lookup.
 * Supports both native validations (with attributes) and custom strategies.
 */
class ValidationRegistry
{
    /** @var array<string, ValidationMetadata> */
    private array $validations = [];
    
    /** @var array<string, array<string>> */
    private array $categories = [];

    /**
     * Register a validation with full metadata
     */
    public function register(ValidationMetadata $metadata): void
    {
        $this->validations[$metadata->name] = $metadata;
        
        if ($metadata->category !== null) {
            if (!isset($this->categories[$metadata->category])) {
                $this->categories[$metadata->category] = [];
            }
            $this->categories[$metadata->category][] = $metadata->name;
        }
    }

    /**
     * Register a simple callable without metadata
     * Used for backward compatibility with registerStrategy()
     */
    public function registerCallable(string $name, callable $validator): void
    {
        $this->validations[$name] = new ValidationMetadata(
            name: $name,
            callable: $validator
        );
    }

    /**
     * Check if a validation is registered
     */
    public function has(string $name): bool
    {
        return isset($this->validations[$name]);
    }

    /**
     * Execute a validation
     * 
     * @throws \InvalidArgumentException if validation not found
     */
    public function execute(string $name, mixed $value, array $args = []): bool
    {
        if (!$this->has($name)) {
            throw new \InvalidArgumentException("Validation '{$name}' not found in registry");
        }
        
        return ($this->validations[$name]->callable)($value, ...$args);
    }

    /**
     * Get metadata for a validation
     */
    public function getMetadata(string $name): ?ValidationMetadata
    {
        return $this->validations[$name] ?? null;
    }

    /**
     * Get all registered validation names
     * 
     * @return array<string>
     */
    public function getAll(): array
    {
        return array_keys($this->validations);
    }

    /**
     * Get all validations with their metadata
     * 
     * @return array<string, ValidationMetadata>
     */
    public function getAllWithMetadata(): array
    {
        return $this->validations;
    }

    /**
     * Get validations by category
     * 
     * @return array<string>
     */
    public function getByCategory(string $category): array
    {
        return $this->categories[$category] ?? [];
    }

    /**
     * Get all categories
     * 
     * @return array<string>
     */
    public function getCategories(): array
    {
        return array_keys($this->categories);
    }

    /**
     * Clear all registrations (useful for testing)
     */
    public function clear(): void
    {
        $this->validations = [];
        $this->categories = [];
    }
}
