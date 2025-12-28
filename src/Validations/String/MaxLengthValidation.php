<?php
namespace Gravity\Validations\String;

use Gravity\Validations\ValidationStrategy;
use Gravity\Attributes\{ValidationRule, Param};

#[ValidationRule(
    name: 'maxLength',
    description: 'Validates that a string does not exceed maximum length',
    category: 'String',
    examples: ['$verifier->field("test")->maxLength(20)']
)]
class MaxLengthValidation extends ValidationStrategy
{
    public function getName(): string
    {
        return 'maxLength';
    }

    protected function handler(
        mixed $value,
        #[Param('Maximum length allowed', example: 20)]
        int $max
    ): bool {
        return strlen((string)$value) <= $max;
    }
}
