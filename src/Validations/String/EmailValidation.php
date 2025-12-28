<?php
namespace Gravity\Validations\String;

use Gravity\Validations\ValidationStrategy;
use Gravity\Attributes\{ValidationRule, Param};

#[ValidationRule(
    name: 'email',
    description: 'Validates that a value is a valid email address',
    category: 'String',
    examples: ['$verifier->field("test")->email']
)]
class EmailValidation extends ValidationStrategy
{
    public function getName(): string
    {
        return 'email';
    }

    protected function handler(
        mixed $value
    ): bool {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
}
