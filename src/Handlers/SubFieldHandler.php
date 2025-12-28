<?php

namespace Gravity\Handlers;

use Gravity\Interfaces\ValidationHandlerInterface;

class SubFieldHandler implements ValidationHandlerInterface
{
    private array $path;
    private array $validations = [];
    private ?string $alias = null;
    private ?string $errorMessage = null;
    private array $conditionalValidations = [];

    public function __construct(array $path)
    {
        $this->path = $path;
    }

    public function addValidation(string $testName, array $arguments = []): void
    {
        $this->validations[$testName] = $arguments;
    }

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