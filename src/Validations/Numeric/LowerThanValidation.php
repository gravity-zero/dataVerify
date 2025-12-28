<?php
namespace Gravity\Validations\Numeric;

use Gravity\Validations\ValidationStrategy;
use Gravity\Attributes\{ValidationRule, Param};

#[ValidationRule(
    name: 'lowerThan',
    description: 'Validates that a value is less than a specified limit',
    category: 'Numeric',
    examples: ['$verifier->field("test")->lowerThan(65)']
)]
class LowerThanValidation extends ValidationStrategy
{
    public function getName(): string
    {
        return 'lowerThan';
    }

    protected function handler(
        mixed $value,
        #[Param('Value must be less than this', example: 65)]
        mixed $max
    ): bool {
        return $value < $max;
    }
}
