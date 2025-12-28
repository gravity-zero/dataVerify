<?php
namespace Gravity\Validations\String;

use Gravity\Validations\ValidationStrategy;
use Gravity\Attributes\{ValidationRule, Param};

#[ValidationRule(
    name: 'ipAddress',
    description: 'Validates that a value is a valid IP address (IPv4 or IPv6)',
    category: 'String',
    examples: ['$verifier->field("test")->ipAddress']
)]
class IpAddressValidation extends ValidationStrategy
{
    public function getName(): string
    {
        return 'ipAddress';
    }

    protected function handler(
        mixed $value
    ): bool {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }
}
