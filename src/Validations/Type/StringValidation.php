<?php
namespace Gravity\Validations\Type;

use Gravity\Validations\ValidationStrategy;
use Gravity\Attributes\{ValidationRule, Param};

#[ValidationRule(
    name: 'string',
    description: 'Validates that a value is a string',
    category: 'Type',
    examples: ['$verifier->field("test")->string']
)]
class StringValidation extends ValidationStrategy
{
    public function getName(): string
    {
        return 'string';
    }

    protected function handler(
        mixed $value
    ): bool {
        return is_string($value);
    }
}
