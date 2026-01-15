<?php

namespace Gravity\Rules;

/**
 * RuleSet - Stores reusable validation rules (without field context)
 * 
 * Rules are field-agnostic and can be applied to any field.
 * They do NOT support conditional logic (when/then).
 */
class RuleSet
{
    private string $name;
    /** @var list<array{name: string, args: list<mixed>}> */
    private array $validations = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Add a validation rule
     * 
     * @param string $testName Validation test name
     * @param list<mixed> $args Validation arguments
     */
    public function addValidation(string $testName, array $args = []): self
    {
        $this->validations[] = ['name' => $testName, 'args' => $args];
        return $this;
    }

    /**
     * Get all validations
     * 
     * @return list<array{name: string, args: list<mixed>}>
     */
    public function getValidations(): array
    {
        return $this->validations;
    }
}
