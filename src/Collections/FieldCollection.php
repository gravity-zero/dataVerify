<?php

namespace Gravity\Collections;

use Gravity\Handlers\FieldHandler;

class FieldCollection implements \IteratorAggregate, \Countable
{
    private array $fields = [];

    public function add(FieldHandler $field): void
    {
        $this->fields[] = $field;
    }

    public function hasField(string $name): bool
    {
        foreach ($this->fields as $field) {
            if ($field->getName() === $name) {
                return true;
            }
        }
        return false;
    }

    public function getField(string $name): ?FieldHandler
    {
        foreach ($this->fields as $field) {
            if ($field->getName() === $name) {
                return $field;
            }
        }
        return null;
    }

    public function getFieldNames(): array
    {
        return array_map(
            fn(FieldHandler $field) => $field->getName(),
            $this->fields
        );
    }

    public function removeField(string $name): bool
    {
        foreach ($this->fields as $index => $field) {
            if ($field->getName() === $name) {
                unset($this->fields[$index]);
                $this->fields = array_values($this->fields); // Re-index
                return true;
            }
        }
        return false;
    }

    public function clear(): void
    {
        $this->fields = [];
    }

    public function isEmpty(): bool
    {
        return empty($this->fields);
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->fields);
    }

    public function count(): int
    {
        return count($this->fields);
    }

    public function toArray(): array
    {
        return $this->fields;
    }
}