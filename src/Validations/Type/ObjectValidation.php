<?php
namespace Gravity\Validations\Type;

use Gravity\Validations\ValidationStrategy;
use Gravity\Attributes\{ValidationRule, Param};

#[ValidationRule(
    name: 'object',
    description: 'Validates that a value is an object',
    category: 'Type',
    examples: ['$verifier->field("test")->object']
)]
class ObjectValidation extends ValidationStrategy
{
    public function getName(): string
    {
        return 'object';
    }

    protected function handler(
        mixed $value
    ): bool {
        return is_object($value);
    }
}
