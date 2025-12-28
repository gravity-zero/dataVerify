<?php
namespace Gravity\Validations\String;

use Gravity\Validations\ValidationStrategy;
use Gravity\Attributes\{ValidationRule, Param};

#[ValidationRule(
    name: 'regex',
    description: 'Validates that a value matches a regular expression pattern. Warnings are suppressed',
    category: 'String',
    examples: ['$verifier->field("test")->regex("/^[A-Z]+$/")']
)]
class RegexValidation extends ValidationStrategy
{
    public function getName(): string
    {
        return 'regex';
    }

    protected function handler(
        mixed $value,
        #[Param('Regular expression pattern', example: '/^[A-Z]+$/')]
        string $pattern
    ): bool {
        if (!is_string($value)) return false;
        $result = @preg_match($pattern, $value);
        if ($result === false) {
            return false;
        }
        return $result === 1;
    }
}
