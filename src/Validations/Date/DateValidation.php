<?php
namespace Gravity\Validations\Date;

use Gravity\Validations\ValidationStrategy;
use Gravity\Attributes\{ValidationRule, Param};

#[ValidationRule(
    name: 'date',
    description: 'Validates that a value is a valid date in the specified format. Performs strict validation',
    category: 'Date',
    examples: ['$verifier->field("test")->date("Y-m-d")']
)]
class DateValidation extends ValidationStrategy
{
    public function getName(): string
    {
        return 'date';
    }

    protected function handler(
        mixed $value,
        #[Param('Date format', example: 'Y-m-d')]
        string $format = "Y-m-d"
    ): bool {
        if ($value instanceof \DateTime) {
            return true;
        }
        if (!is_string($value)) {
            return false;
        }
        $dateTime = \DateTime::createFromFormat($format, $value);
        if ($dateTime === false) {
            return false;
        }
        return $dateTime->format($format) === $value;
    }
}
