<?php

namespace Gravity\Context;

use Gravity\Handlers\FieldHandler;
use Gravity\Handlers\SubFieldHandler;

class ValidationContext
{
    /** @var list<FieldHandler|SubFieldHandler> */
    private array $stack = [];

    public function push(FieldHandler|SubFieldHandler $handler): void
    {
        $this->stack[] = $handler;
    }

    public function current(): FieldHandler|SubFieldHandler|null
    {
        return end($this->stack) ?: null;
    }

    public function lastField(): ?FieldHandler
    {
        for ($i = count($this->stack) - 1; $i >= 0; $i--) {
            if ($this->stack[$i] instanceof FieldHandler) {
                return $this->stack[$i];
            }
        }
        return null;
    }
}