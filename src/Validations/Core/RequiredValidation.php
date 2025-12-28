<?php
namespace Gravity\Validations\Core;

use Gravity\Validations\ValidationStrategy;
use Gravity\Attributes\{ValidationRule, Param};

#[ValidationRule(
    name: 'required',
    description: 'Validates that a field is not empty. Objects are cast to arrays to check emptiness.',
    category: 'Core',
    examples: ['$verifier->field("test")->required']
)]
class RequiredValidation extends ValidationStrategy
{
    public function getName(): string
    {
        return 'required';
    }

    protected function handler(
        mixed $value
    ): bool {
        if (is_object($value)) {
            return !empty((array)$value);
        }
        if (is_bool($value)) {
            return true;
        }
        return !(empty($value) && !is_numeric($value));
    }
}
