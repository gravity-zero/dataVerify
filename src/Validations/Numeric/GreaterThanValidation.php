<?php
namespace Gravity\Validations\Numeric;

use Gravity\Validations\ValidationStrategy;
use Gravity\Attributes\{ValidationRule, Param};

#[ValidationRule(
    name: 'greaterThan',
    description: 'Validates that a value is greater than a specified limit',
    category: 'Numeric',
    examples: ['$verifier->field("test")->greaterThan(18)']
)]
class GreaterThanValidation extends ValidationStrategy
{
    public function getName(): string
    {
        return 'greaterThan';
    }

    protected function handler(
        mixed $value,
        #[Param('Value must be greater than this', example: 18)]
        mixed $min
    ): bool {
        return $value > $min;
    }
}
