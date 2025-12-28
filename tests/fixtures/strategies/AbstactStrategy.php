<?php

namespace Tests\Fixtures\Strategies;

use Gravity\Interfaces\ValidationStrategyInterface;

abstract class AbstractStrategy implements ValidationStrategyInterface
{
    public function getName(): string
    {
        return 'abstract';
    }

    abstract public function execute(mixed $value, array $args): bool;
}