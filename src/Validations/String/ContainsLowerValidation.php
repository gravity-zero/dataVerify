<?php
namespace Gravity\Validations\String;

use Gravity\Validations\ValidationStrategy;
use Gravity\Attributes\{ValidationRule, Param};

#[ValidationRule(
    name: 'containsLower',
    description: 'Validates that a string contains at least one lowercase letter',
    category: 'String',
    examples: ['$verifier->field("test")->containsLower']
)]
class ContainsLowerValidation extends ValidationStrategy
{
    public function getName(): string
    {
        return 'containsLower';
    }

    protected function handler(
        mixed $value
    ): bool {
        if (!is_string($value)) {
            return false;
        }
        foreach(str_split($value) as $char) {
            if(ctype_lower($char) && !is_numeric($char)) return true;
        }
        return false;
    }
}
