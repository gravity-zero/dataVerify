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

    /** @return list<ConditionalValidation> */
    public function getConditionalValidations(): array { return $this->conditionalValidations; }
    public function getName(): string { return $this->name; }
    /** @return list<array{name: string, args: list<mixed>}> */
    public function getValidations(): array { return $this->validations; }
    /** @return list<array{name: string, args: list<mixed>, conditions: array|null}> */
    public function getAllValidations(): array { return $this->allValidations; }
    public function getSubFields(): SubFieldCollection  { return $this->subFields; }
    public function getAlias(): ?string { return $this->alias; }
    public function setAlias(string $alias): void { $this->alias = $alias; }
    public function getErrorMessage(): ?string { return $this->errorMessage; }
    public function setErrorMessage(string $message): void { $this->errorMessage = $message; }
}
