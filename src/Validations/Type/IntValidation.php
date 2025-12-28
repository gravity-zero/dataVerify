<?php
namespace Gravity\Validations\Type;

use Gravity\Validations\ValidationStrategy;
use Gravity\Attributes\{ValidationRule, Param};

#[ValidationRule(
    name: 'int',
    description: 'Validates that a value is an integer. In strict mode (default), only true integers are accepted',
    category: 'Type',
    examples: ['$verifier->field("test")->int(true)']
)]
class IntValidation extends ValidationStrategy
{
    public function getName(): string
    {
        return 'int';
    }

    protected function handler(
        mixed $value,
        #[Param('Strict mode: true for integers only', example: true)]
        bool $strict = true
    ): bool {
        return $strict ? is_int($value) : (is_string($value) && filter_var($value, FILTER_VALIDATE_INT));
    }
}
