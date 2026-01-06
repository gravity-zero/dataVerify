<?php
namespace Gravity\Validations\String;

use Gravity\Validations\ValidationStrategy;
use Gravity\Attributes\{ValidationRule, Param};
use Gravity\Config\DisposableEmailDomains;

#[ValidationRule(
    name: 'disposableEmail',
    description: 'Validates that an email is not from a disposable domain, can be overriden ["@yourDisposableDomain", ...]',
    category: 'String',
    examples: ['$verifier->field("email")->disposableEmail',
               '$verifier->field("email")->disposableEmail(["@yourDisposableDomain", ...])']
)]
class DisposableEmailValidation extends ValidationStrategy
{
    public function getName(): string
    {
        return 'disposableEmail';
    }

    protected function handler(
        mixed $value,
        #[Param('Array of disposable domain patterns', example: [])]
        array $disposables = []
    ): bool {
        if (!is_string($value) || !str_contains($value, '@')) {
            return false;
        }
        $list = !empty($disposables) ? $disposables : DisposableEmailDomains::DEFAULTS;
        $domainPart = substr(strstr($value, '@'), 1);
        if ($domainPart === false || $domainPart === '') {
            return false;
        }
        foreach ($list as $disposable) {
            $disposableDomain = ltrim($disposable, '@');
            if (str_starts_with($domainPart, $disposableDomain) || 
                str_contains($domainPart, '.' . $disposableDomain)) {
                return false;
            }
        }
        return true;
    }
}
