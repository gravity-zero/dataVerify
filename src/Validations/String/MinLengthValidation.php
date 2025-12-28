<?php
namespace Gravity\Validations\String;

use Gravity\Validations\ValidationStrategy;
use Gravity\Attributes\{ValidationRule, Param};

#[ValidationRule(
    name: 'minLength',
    description: 'Validates that a string has a minimum length',
    category: 'String',
    examples: ['$verifier->field("test")->minLength(8)']
)]
class MinLengthValidation extends ValidationStrategy
{
    public function getName(): string
    {
        return 'minLength';
    }

    protected function handler(
        mixed $value,
        #[Param('Minimum length required', example: 8)]
        int $min
    ): bool {
        return strlen((string)$value) >= $min;
    }
}
