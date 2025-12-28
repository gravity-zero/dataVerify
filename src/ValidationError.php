<?php

namespace Gravity;

use stdClass;
use Gravity\Interfaces\ValidationErrorInterface;

class ValidationError implements ValidationErrorInterface
{
    private string $field;
    private ?string $alias;
    private string $test;
    private string $message;
    private mixed $value;

    public function __construct(string $field, ?string $alias, string $test, string $message, mixed $value)
    {
        $this->field = $field;
        $this->alias = $alias;
        $this->test = $test;
        $this->message = $message;
        $this->value = $value;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function getTest(): string
    {
        return $this->test;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function toObject(): object
    {
        $stdClass = new stdClass();
        $stdClass->field = $this->getField();
        $stdClass->alias = $this->getAlias();
        $stdClass->test = $this->getTest();
        $stdClass->message = $this->getMessage();
        $stdClass->value = $this->getValue();

        return $stdClass;
    }

    public function toArray(): array
    {
        return [
            'field' => $this->getField(),
            'alias' => $this->getAlias(),
            'test' => $this->getTest(),
            'message' => $this->getMessage(),
            'value' => $this->getValue(),
        ];
    }
}