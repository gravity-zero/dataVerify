<?php
namespace Gravity\Validations\String;

use Gravity\Validations\ValidationStrategy;
use Gravity\Attributes\{ValidationRule, Param};

#[ValidationRule(
    name: 'containsSpecialCharacter',
    description: 'Validates that a string contains at least one special character',
    category: 'String',
    examples: ['$verifier->field("test")->containsSpecialCharacter']
)]
class ContainsSpecialCharacterValidation extends ValidationStrategy
{
    public function getName(): string
    {
        return 'containsSpecialCharacter';
    }

    protected function handler(
        mixed $value
    ): bool {
        if (!is_string($value)) {
            return false;
        }
        return preg_match('/[^\w\s]/u', $value) === 1;
    }
}
