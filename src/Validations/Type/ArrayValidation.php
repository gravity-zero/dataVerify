<?php
namespace Gravity\Validations\Type;

use Gravity\Validations\ValidationStrategy;
use Gravity\Attributes\{ValidationRule, Param};

#[ValidationRule(
    name: 'array',
    description: 'Validates that a value is an array',
    category: 'Type',
    examples: ['$verifier->field("test")->array']
)]
class ArrayValidation extends ValidationStrategy
{
    public function getName(): string
    {
        return 'array';
    }

    protected function handler(
        mixed $value
    ): bool {
        return is_array($value);
    }
}
