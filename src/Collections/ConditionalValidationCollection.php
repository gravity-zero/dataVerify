<?php

namespace Gravity\Collections;

use Gravity\Handlers\ConditionalValidation;

class ConditionalValidationCollection
{
    private array $conditionals = [];
    
    public function add(ConditionalValidation $conditional): void
    {
        $this->conditionals[] = $conditional;
    }
    
    public function all(): array
    {
        return $this->conditionals;
    }
}