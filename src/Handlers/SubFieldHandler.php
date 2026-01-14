<?php

namespace Gravity\Handlers;

use Gravity\Interfaces\ValidationHandlerInterface;

class SubFieldHandler implements ValidationHandlerInterface
{
    /** @var list<array{name: string, args: list<mixed>}> */
    private array $validations = [];
    /** @var ConditionalValidation[] */
    private array $conditionalValidations = [];
    /** @var list<array{name: string, args: list<mixed>, conditions: array{conditions: array, operator: \Gravity\Enums\ConditionOperator}|null}> */
    private array $allValidations = [];
    private ?string $alias = null;
    private ?string $errorMessage = null;
    private array $path;

    

    public function __construct(array $path)
    {
        $this->path = $path;
    }

    /**
     * @param list<mixed> $arguments
     * @param array{conditions: array, operator: \Gravity\Enums\ConditionOperator}|null $conditions
     */
    public function addValidation(string $testName, array $arguments = [], ?array $conditions = null): void
    {
        $this->allValidations[] = [
            'name' => $testName, 
            'args' => $arguments,
            'conditions' => $conditions
        ];
        
        // Keep old API for backward compatibility
        if ($conditions === null) {
            $this->validations[] = ['name' => $testName, 'args' => $arguments];
        }
    }

    /**
     * @param list<mixed> $args
     * @param array{field: string, operator: string, value: mixed} $condition
     */
    public function addConditionalValidation(string $testName, array $args, array $condition): void
    {
        $this->conditionalValidations[] = new ConditionalValidation(
            $condition['field'],
            $condition['operator'],
            $condition['value'],
            $testName,
            $args
        );
    }

    public function getConditionalValidations(): array { return $this->conditionalValidations; }
    public function getPath(): array { return $this->path; }
    
    public function getValidations(): array { return $this->validations; }
    /** @return list<array{name: string, args: list<mixed>, conditions: array|null}> */
    public function getAllValidations(): array { return $this->allValidations; }
    public function getAlias(): ?string { return $this->alias; }
    public function setAlias(string $alias): void { $this->alias = $alias; }
    public function getErrorMessage(): ?string { return $this->errorMessage; }
    public function setErrorMessage(string $message): void { $this->errorMessage = $message; }
}
