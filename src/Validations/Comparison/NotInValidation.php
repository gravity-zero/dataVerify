<?php
namespace Gravity\Validations\Comparison;

use Gravity\Validations\ValidationStrategy;
use Gravity\Attributes\{ValidationRule, Param};

#[ValidationRule(
    name: 'notIn',
    description: 'Validates that a value does not exist in a forbidden list or as an object property',
    category: 'Comparison',
    examples: ['$verifier->field("test")->notIn(["admin", "root"])']
)]
class NotInValidation extends ValidationStrategy
{
    public function getName(): string
    {
        return 'notIn';
    }

    protected function handler(
        mixed $value,
        #[Param('Array or object of forbidden values', example: ["admin", "root"])]
        array|object $forbidden
    ): bool {
        if (is_array($forbidden)) {
            return !in_array($value, $forbidden, true);
        }
        if (is_object($forbidden)) {
            return !property_exists($forbidden, $value);
        }
        return true;
    }
}
