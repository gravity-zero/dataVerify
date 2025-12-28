<?php

namespace Gravity\Handlers;

use Gravity\Interfaces\ValidationHandlerInterface;
use Gravity\Collections\SubFieldCollection;


class FieldHandler implements ValidationHandlerInterface
{
    private string $name;
    private array $validations = [];
    private SubFieldCollection $subFields;
    private ?string $alias = null;
    private ?string $errorMessage = null;
    /** @var ConditionalValidation[] */
    private array $conditionalValidations = [];

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->subFields = new SubFieldCollection();
    }

    public function addSubField(SubFieldHandler $subField): void
    {
        $this->subFields->add($subField);
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
    public function getName(): string { return $this->name; }
    public function getValidations(): array { return $this->validations; }
    public function getSubFields(): SubFieldCollection  { return $this->subFields; }
    public function getAlias(): ?string { return $this->alias; }
    public function setAlias(string $alias): void { $this->alias = $alias; }
    public function getErrorMessage(): ?string { return $this->errorMessage; }
    public function setErrorMessage(string $message): void { $this->errorMessage = $message; }
}