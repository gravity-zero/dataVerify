<?php

namespace Gravity\Handlers;

use Gravity\Interfaces\ValidationHandlerInterface;
use Gravity\Collections\SubFieldCollection;


class FieldHandler implements ValidationHandlerInterface
{

    /** @var list<array{name: string, args: list<mixed>, conditions: array{conditions: array, operator: \Gravity\Enums\ConditionOperator}|null}> */
    private array $allValidations = [];
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

    public function getName(): string { return $this->name; }
    /** @return list<array{name: string, args: list<mixed>, conditions: array|null}> */
    public function getAllValidations(): array { return $this->allValidations; }
    public function getSubFields(): SubFieldCollection  { return $this->subFields; }
    public function getAlias(): ?string { return $this->alias; }
    public function setAlias(string $alias): void { $this->alias = $alias; }
    public function getErrorMessage(): ?string { return $this->errorMessage; }
    public function setErrorMessage(string $message): void { $this->errorMessage = $message; }
}
