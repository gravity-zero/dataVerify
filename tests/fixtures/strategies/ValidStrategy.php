<?php

namespace Tests\Fixtures\Strategies;

use Gravity\Interfaces\ValidationStrategyInterface;

class ValidStrategy implements ValidationStrategyInterface
{
    public function getName(): string
    {
        return 'another_valid';
    }

    public function execute(mixed $value, array $args): bool
    {
        return true;
    }
}