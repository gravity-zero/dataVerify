<?php

namespace Gravity\Collections;

use Gravity\Handlers\SubFieldHandler;

final class SubFieldCollection implements \IteratorAggregate, \Countable
{
    /** @var list<SubFieldHandler> */
    private array $subFields = [];

    public function add(SubFieldHandler $subField): void
    {
        $this->subFields[] = $subField;
    }

    /** @param list<string> $path */
    public function hasSubField(array $path): bool
    {
        foreach ($this->subFields as $subField) {
            if ($subField->getPath() === $path) {
                return true;
            }
        }
        return false;
    }

    /** @param list<string> $path */
    public function getSubField(array $path): ?SubFieldHandler
    {
        foreach ($this->subFields as $subField) {
            if ($subField->getPath() === $path) {
                return $subField;
            }
        }
        return null;
    }

    /** @return list<list<string>> */
    public function getSubFieldPaths(): array
    {
        return array_map(
            fn(SubFieldHandler $subField) => $subField->getPath(),
            $this->subFields
        );
    }

    /** @param list<string> $path */
    public function removeSubField(array $path): bool
    {
        foreach ($this->subFields as $index => $subField) {
            if ($subField->getPath() === $path) {
                unset($this->subFields[$index]);
                $this->subFields = array_values($this->subFields);
                return true;
            }
        }
        return false;
    }

    public function clear(): void
    {
        $this->subFields = [];
    }

    public function isEmpty(): bool
    {
        return empty($this->subFields);
    }

    /**
     * @return \Traversable<int, SubFieldHandler>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->subFields);
    }

    public function count(): int
    {
        return count($this->subFields);
    }

    /** @return list<SubFieldHandler> */
    public function toArray(): array
    {
        return $this->subFields;
    }
}