<?php
namespace Gravity\Validations\Numeric;

use Gravity\Validations\ValidationStrategy;
use Gravity\Attributes\{ValidationRule, Param};

#[ValidationRule(
    name: 'between',
    description: 'Validates that a value is between two bounds. Supports numeric values and DateTime objects',
    category: 'Numeric',
    examples: ['$verifier->field("test")->between(18, 65)']
)]
class BetweenValidation extends ValidationStrategy
{
    public function getName(): string
    {
        return 'between';
    }

    protected function handler(
        mixed $value,
        #[Param('Minimum value (inclusive)', example: 18)]
        int|float|\DateTime|string $min,
        #[Param('Maximum value (inclusive)', example: 65)]
        int|float|\DateTime|string $max
    ): bool {
        if ($value instanceof \DateTime && $min instanceof \DateTime && $max instanceof \DateTime) {
            return $value >= $min && $value <= $max;
        }
        if (is_numeric($value) && is_numeric($min) && is_numeric($max)) {
            $value = $value + 0;
            $min = $min + 0;
            $max = $max + 0;
            return $value >= $min && $value <= $max;
        }
        return false;
    }
}
