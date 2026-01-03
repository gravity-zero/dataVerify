<?php

namespace Gravity\Validations\String;

use Gravity\Validations\ValidationStrategy;
use Gravity\Attributes\{ValidationRule, Param};


#[ValidationRule(
    name: 'url',
    description: 'Validates that a value is a valid URL with configurable schemes and TLD requirement',
    category: 'String',
    examples: [
        '$verifier->field("website")->url',
        '$verifier->field("api")->url(["http", "https", "ws", "wss"])',
        '$verifier->field("intranet")->url(["http"], requireTld: false)'
    ]
)]
class UrlValidation extends ValidationStrategy
{
    public function getName(): string
    {
        return 'url';
    }

    protected function handler(
        mixed $value,
        #[Param('Allowed URL schemes', example: ['http', 'https'])]
        array $schemes = ['http', 'https'],
        #[Param('Require a top-level domain (.com|.org|.net|...)', example: true)]
        bool $requireTld = true
    ): bool {
        if (!is_string($value) || $value === '') {
            return false;
        }

        $scheme = parse_url($value, PHP_URL_SCHEME);
        
        if ($scheme === null || $scheme === false) {
            return false;
        }

        $normalizedScheme = strtolower($scheme);
        $normalizedSchemes = array_map('strtolower', $schemes);
        
        if (!in_array($normalizedScheme, $normalizedSchemes, true)) {
            return false;
        }

        $host = parse_url($value, PHP_URL_HOST);
                    
        if (!is_string($host) || $host === '') {
            return false;
        }

        $hostForIp = trim($host, '[]');

        $isIpv6 = filter_var($hostForIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
        
        if ($isIpv6) {
            return true;
        }

        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        if ($requireTld) {

            if (filter_var($hostForIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
                return true;
            }

            if (!str_contains($host, '.')) {
                return false;
            }
        }

        return true;
    }
}
