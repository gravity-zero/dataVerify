<?php
namespace Gravity\Validations\Type;

use Gravity\Validations\ValidationStrategy;
use Gravity\Attributes\{ValidationRule, Param};

#[ValidationRule(
    name: 'boolean',
    description: 'Validates that a value is a boolean. In strict mode (default), only true/false are accepted',
    category: 'Type',
    examples: ['$verifier->field("test")->boolean(true)']
)]
class BooleanValidation extends ValidationStrategy
{
    public function getName(): string
    {
        return 'boolean';
    }

    protected function handler(
        mixed $value,
        #[Param('Strict mode: true for booleans only', example: true)]
        bool $strict = true
    ): bool {
        if ($strict) {
            return is_bool($value);
        }
        return in_array($value, [true, false, 1, 0, "1", "0"], true);
    }
}
