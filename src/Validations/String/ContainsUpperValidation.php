<?php
namespace Gravity\Validations\String;

use Gravity\Validations\ValidationStrategy;
use Gravity\Attributes\{ValidationRule, Param};

#[ValidationRule(
    name: 'containsUpper',
    description: 'Validates that a string contains at least one uppercase letter',
    category: 'String',
    examples: ['$verifier->field("test")->containsUpper']
)]
class ContainsUpperValidation extends ValidationStrategy
{
    public function getName(): string
    {
        return 'containsUpper';
    }

    protected function handler(
        mixed $value
    ): bool {
        if (!is_string($value)) {
            return false;
        }
        foreach(str_split($value) as $char) {
            if(ctype_upper($char) && !is_numeric($char)) return true;
        }
        return false;
    }
}
