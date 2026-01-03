<?php

namespace Gravity\Validations\String;

use Gravity\Validations\ValidationStrategy;
use Gravity\Attributes\{ValidationRule, Param};

#[ValidationRule(
    name: 'disposableUrlDomain',
    description: 'Validates that a URL is not from a disposable/temporary domain (URL shorteners, free hosting, etc.)',
    category: 'String',
    examples: [
        '$verifier->field("website")->disposableUrlDomain',
        '$verifier->field("website")->disposableUrlDomain(["bit.ly", "tinyurl.com"])'
    ]
)]
class DisposableUrlDomainValidation extends ValidationStrategy
{
    /**
     * List of known disposable/temporary URL domain patterns
     * 
     * Categories:
     * - URL Shorteners
     * - Temporary file hosting
     * - Free subdomain services
     * - Suspicious free hosting
     * 
     * @var array<string>
     */
    public const DEFAULTS = [
        'bit.ly','tinyurl.com','goo.gl','ow.ly','t.co','is.gd','buff.ly','adf.ly','bc.vc','soo.gd','clk.im','s2r.co','shrtco.de',
        'rb.gy','cutt.ly','short.io','tiny.cc','file.io','transfer.sh','temp.sh','tmpfiles.org','0x0.st','uguu.se','catbox.moe',
        'litterbox.catbox.moe','pixeldrain.com','gofile.io','anonfiles.com','bayfiles.com','000webhostapp.com','freehosting.com',
        'freehostia.com','x10hosting.com','byethost.com','5gbfree.com','freewha.com','ngrok.io','ngrok-free.app','localtunnel.me',
        'serveo.net','localhost.run',
        ];

    public function getName(): string
    {
        return 'disposableUrlDomain';
    }

        protected function handler(
        mixed $value,
        #[Param('Array of disposable domain patterns', example: [])]
        array $disposables = []
    ): bool {
        if (!is_string($value) || $value === '') {
            return false;
        }

        $host = parse_url($value, PHP_URL_HOST);
        
        if ($host === null || $host === false || $host === '') {
            return false;
        }

        $list = !empty($disposables) ? $disposables : self::DEFAULTS;

        $host = strtolower($host);

        foreach ($list as $disposable) {
            $disposableDomain = strtolower(ltrim($disposable, '.'));

            if ($host === $disposableDomain) {
                return false;
            }

            if (str_ends_with($host, '.' . $disposableDomain)) {
                return false;
            }
        }

        return true;
    }
}
