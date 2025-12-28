<?php

namespace Gravity\Context;

use Gravity\Handlers\FieldHandler;

class ValidationContext
{
    private array $stack = [];

    public function push($handler): void
    {
        $this->stack[] = $handler;
    }

    public function current()
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