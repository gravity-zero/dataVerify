<?php

namespace Tests\Fixtures\Strategies;

// This class does NOT implement ValidationStrategyInterface
class NotAStrategy
{
    public function getName(): string
    {
        return 'not_a_strategy';
    }
}