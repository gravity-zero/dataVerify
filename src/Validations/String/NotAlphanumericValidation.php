<?php
namespace Gravity\Validations\String;

use Gravity\Validations\ValidationStrategy;
use Gravity\Attributes\{ValidationRule, Param};

#[ValidationRule(
    name: 'notAlphanumeric',
    description: 'Validates that a value contains non-alphanumeric characters',
    category: 'String',
    examples: ['$verifier->field("test")->notAlphanumeric']
)]
class NotAlphanumericValidation extends ValidationStrategy
{
    public function getName(): string
    {
        return 'notAlphanumeric';
    }

    protected function handler(
        mixed $value
    ): bool {
        if (!is_string($value)) {
            return false;
        }
        return !preg_match('/[\p{L}\p{N}\p{M}]/u', $value);
    }
}
