<?php
namespace Gravity\Validations\Comparison;

use Gravity\Validations\ValidationStrategy;
use Gravity\Attributes\{ValidationRule, Param};

#[ValidationRule(
    name: 'in',
    description: 'Validates that a value exists in an allowed list or as an object property',
    category: 'Comparison',
    examples: ['$verifier->field("test")->in(["active", "pending"])']
)]
class InValidation extends ValidationStrategy
{
    public function getName(): string
    {
        return 'in';
    }

    protected function handler(
        mixed $value,
        #[Param('Array or object of allowed values', example: ["active", "pending"])]
        array|object $allowed
    ): bool {
        if (is_array($allowed)) {
            return in_array($value, $allowed, true);
        }
        if (is_object($allowed)) {
            return property_exists($allowed, $value);
        }
        return false;
    }
}
