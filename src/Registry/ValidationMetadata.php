<?php

namespace Gravity\Registry;

/**
 * Holds metadata about a validation rule
 * 
 * Stores all information about a validation including its callable,
 * description, parameters, and examples.
 */
class ValidationMetadata
{
    public function __construct(
        public readonly string $name,
        public readonly \Closure $callable,
        public readonly ?string $description = null,
        public readonly ?string $category = null,
        public readonly array $examples = [],
        public readonly array $parameters = []
    ) {}

    /**
     * Convert metadata to array format
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'examples' => $this->examples,
            'parameters' => $this->parameters
        ];
    }

    /**
     * Check if this validation has metadata (vs simple callable)
     */
    public function hasMetadata(): bool
    {
        return $this->description !== null 
            || $this->category !== null 
            || !empty($this->examples)
            || !empty($this->parameters);
    }

    /**
     * Get validation parameters
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
    
    /**
     * Get validation description
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }
    
    /**
     * Get validation category
     */
    public function getCategory(): ?string
    {
        return $this->category;
    }
    
    /**
     * Get validation examples
     */
    public function getExamples(): array
    {
        return $this->examples;
    }
}
