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
     * Get all validations including those with conditions
     * 
     * @return list<array{name: string, args: list<mixed>, conditions: array{conditions: array, operator: \Gravity\Enums\ConditionOperator}|null}>
     */
    public function getAllValidations(): array;
    
    public function getAlias(): ?string;
    public function setAlias(string $alias): void;
    public function getErrorMessage(): ?string;
    public function setErrorMessage(string $message): void;
}
