<?php

namespace Gravity\Handlers;

use Gravity\Interfaces\ValidationHandlerInterface;

class SubFieldHandler implements ValidationHandlerInterface
{
    /** @var list<array{name: string, args: list<mixed>}> */
    private array $validations = [];
    /** @var ConditionalValidation[] */
    private array $conditionalValidations = [];
    private ?string $alias = null;
    private ?string $errorMessage = null;
    private array $path;

    

    public function __construct(array $path)
    {
        $this->path = $path;
    }

    /**
     * @param list<mixed> $arguments
     */
    public function addValidation(string $testName, array $arguments = []): void
    {
        $this->validations[] = ['name' => $testName, 'args' => $arguments];
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
    public function getAlias(): ?string { return $this->alias; }
    public function setAlias(string $alias): void { $this->alias = $alias; }
    public function getErrorMessage(): ?string { return $this->errorMessage; }
    public function setErrorMessage(string $message): void { $this->errorMessage = $message; }
}