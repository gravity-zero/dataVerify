<?php
namespace Gravity\Validations\Type;

use Gravity\Validations\ValidationStrategy;
use Gravity\Attributes\{ValidationRule, Param};

#[ValidationRule(
    name: 'numeric',
    description: 'Validates that a value is numeric',
    category: 'Type',
    examples: ['$verifier->field("test")->numeric']
)]
class NumericValidation extends ValidationStrategy
{
    public function getName(): string
    {
        return 'numeric';
    }

    protected function handler(
        mixed $value
    ): bool {
        return is_numeric($value);
    }
}
