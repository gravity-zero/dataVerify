<?php
namespace Gravity\Validations\String;

use Gravity\Validations\ValidationStrategy;
use Gravity\Attributes\{ValidationRule, Param};

#[ValidationRule(
    name: 'containsNumber',
    description: 'Validates that a string contains at least one digit',
    category: 'String',
    examples: ['$verifier->field("test")->containsNumber']
)]
class ContainsNumberValidation extends ValidationStrategy
{
    public function getName(): string
    {
        return 'containsNumber';
    }

    protected function handler(
        mixed $value
    ): bool {
        if (!is_string($value)) {
            return false;
        }
        foreach(str_split($value) as $char) {
            if(is_numeric($char)) return true;
        }
        return false;
    }
}
