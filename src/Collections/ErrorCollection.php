<?php

namespace Gravity\Collections;

use Gravity\ValidationError;

class ErrorCollection implements \IteratorAggregate, \Countable
{
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

    public function getErrorsByField(string $fieldName): array
    {
        return array_filter(
            $this->errors,
            fn(ValidationError $error) => $error->getField() === $fieldName
        );
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

    public function toArray(): array
    {
        return array_map(
            fn(ValidationError $error) => $error->toArray(),
            $this->errors
        );
    }

    public function toObjects(): array
    {
        return array_map(
            fn(ValidationError $error) => $error->toObject(),
            $this->errors
        );
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->errors);
    }

    public function count(): int
    {
        return count($this->errors);
    }
}