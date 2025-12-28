<?php
namespace Gravity\Validations\Type;

use Gravity\Validations\ValidationStrategy;
use Gravity\Attributes\{ValidationRule, Param};

#[ValidationRule(
    name: 'json',
    description: 'Validates that a string is valid JSON',
    category: 'Type',
    examples: ['$verifier->field("test")->json']
)]
class JsonValidation extends ValidationStrategy
{
    public function getName(): string
    {
        return 'json';
    }

    protected function handler(
        mixed $value
    ): bool {
        if (!is_string($value)) {
            return false;
        }
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
