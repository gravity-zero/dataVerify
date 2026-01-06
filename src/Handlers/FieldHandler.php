<?php

namespace Gravity\Handlers;

use Gravity\Interfaces\ValidationHandlerInterface;
use Gravity\Collections\SubFieldCollection;


class FieldHandler implements ValidationHandlerInterface
{

    /** @var list<array{name: string, args: list<mixed>}> */
    private array $validations = [];
    /** @var ConditionalValidation[] */
    private array $conditionalValidations = [];
    private SubFieldCollection $subFields;
    private string $name;
    private ?string $alias = null;
    private ?string $errorMessage = null;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->subFields = new SubFieldCollection();
    }

    public function addSubField(SubFieldHandler $subField): void
    {
        $this->subFields->add($subField);
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

    /** @return list<ConditionalValidation> */
    public function getConditionalValidations(): array { return $this->conditionalValidations; }
    public function getName(): string { return $this->name; }
    /** @return list<array{name: string, args: list<mixed>}> */
    public function getValidations(): array { return $this->validations; }
    public function getSubFields(): SubFieldCollection  { return $this->subFields; }
    public function getAlias(): ?string { return $this->alias; }
    public function setAlias(string $alias): void { $this->alias = $alias; }
    public function getErrorMessage(): ?string { return $this->errorMessage; }
    public function setErrorMessage(string $message): void { $this->errorMessage = $message; }
}