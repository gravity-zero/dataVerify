<?php

namespace Gravity\Collections;

use Gravity\ValidationError;

class ErrorCollection implements \IteratorAggregate, \Countable
{
    /** @var list<ValidationError> */
    private array $errors = [];

    public function add(ValidationError $error): void
    {
        $this->errors[] = $error;
    }

    public function hasErrorForField(string $fieldName): bool
    {
        foreach ($this->errors as $error) {
            if ($error->getField() === $fieldName) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return list<ValidationError>
     */
    public function getErrorsByField(string $fieldName): array
    {
        return array_values(array_filter(
            $this->errors,
            fn(ValidationError $error) => $error->getField() === $fieldName
        ));
    }

    public function getFirstError(): ?ValidationError
    {
        return $this->errors[0] ?? null;
    }

    public function getLastError(): ?ValidationError
    {
        return end($this->errors) ?: null;
    }

    public function clear(): void
    {
        $this->errors = [];
    }

    public function isEmpty(): bool
    {
        return empty($this->errors);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(
            fn(ValidationError $error): array => $error->toArray(),
            $this->errors
        );
    }

    /**
     * @return list<object>
     */
    public function toObjects(): array
    {
        return array_map(
            fn(ValidationError $error) => $error->toObject(),
            $this->errors
        );
    }

    /**
     * @return ArrayIterator<int, ValidationError>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->errors);
    }

    public function count(): int
    {
        return count($this->errors);
    }
}