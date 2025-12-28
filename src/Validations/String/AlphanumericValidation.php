<?php
namespace Gravity\Validations\String;

use Gravity\Validations\ValidationStrategy;
use Gravity\Attributes\{ValidationRule, Param};

#[ValidationRule(
    name: 'alphanumeric',
    description: 'Validates that a value contains only alphanumeric characters',
    category: 'String',
    examples: ['$verifier->field("test")->alphanumeric']
)]
class AlphanumericValidation extends ValidationStrategy
{
    public function getName(): string
    {
        return 'alphanumeric';
    }

    protected function handler(
        mixed $value
    ): bool {
        return is_string($value) && ctype_alnum($value);
    }
}
