<?php

namespace Gravity\Interfaces;

interface ValidationHandlerInterface
{
    /**
     * Add a validation with optional conditions for deferred evaluation
     * 
     * @param string $testName Validation test name
     * @param list<mixed> $arguments Validation arguments
     * @param array{conditions: array, operator: \Gravity\Enums\ConditionOperator}|null $conditions Optional conditions
     */
    public function addValidation(string $testName, array $arguments = [], ?array $conditions = null): void;
    
    /**
     * Get validations without conditions (backward compatibility)
     * 
     * @return list<array{name: string, args: list<mixed>}>
     */
    public function getValidations(): array;
    
    /**
     * Get all validations including those with conditions
     * 
     * @return list<array{name: string, args: list<mixed>, conditions: array{conditions: array, operator: \Gravity\Enums\ConditionOperator}|null}>
     */
    public function getAllValidations(): array;
    
    public function getAlias(): ?string;
    public function setAlias(string $alias): void;
    public function getErrorMessage(): ?string;
    public function setErrorMessage(string $message): void;
    
    /**
     * Add conditional validation (old API, kept for backward compatibility)
     * 
     * @param string $testName Validation test name
     * @param list<mixed> $args Validation arguments
     * @param array{field: string, operator: string, value: mixed} $condition Single condition
     */
    public function addConditionalValidation(string $testName, array $args, array $condition): void;
    
    /**
     * Get old-style conditional validations
     * 
     * @return list<\Gravity\Handlers\ConditionalValidation>
     */
    public function getConditionalValidations(): array;
}