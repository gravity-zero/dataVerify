<?php

namespace Gravity\Handlers;

use Gravity\Interfaces\ValidationHandlerInterface;

class SubFieldHandler implements ValidationHandlerInterface
{
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
    }

    public function getPath(): array { return $this->path; }
    /** @return list<array{name: string, args: list<mixed>, conditions: array|null}> */
    public function getAllValidations(): array { return $this->allValidations; }
    public function getAlias(): ?string { return $this->alias; }
    public function setAlias(string $alias): void { $this->alias = $alias; }
    public function getErrorMessage(): ?string { return $this->errorMessage; }
    public function setErrorMessage(string $message): void { $this->errorMessage = $message; }
}
