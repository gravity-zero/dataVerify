<?php

namespace Tests\Fixtures\Strategies;

use Gravity\Interfaces\ValidationStrategyInterface;
use Gravity\Attributes\ValidationRule;

#[ValidationRule(
    name: 'valid_fixture',
    description: 'Test fixture strategy',
    category: 'Test'
)]
class AnnotationValidStrategy implements ValidationStrategyInterface
{
    public function getName(): string
    {
        return 'valid_fixture';
    }

    public function execute(mixed $value, array $args): bool
    {
        return $value === 'valid';
    }
}