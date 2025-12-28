<?php

require __DIR__ . '/../vendor/autoload.php';

use Gravity\DataVerify;
use Gravity\Validations\ValidationStrategy;

echo "=== Translation - Custom Validations ===\n\n";

// Example 1: Custom validation with addTranslations()
echo "1. Custom strategy with inline translations\n";

class IsPalindromeStrategy extends ValidationStrategy
{
    public function getName(): string
    {
        return 'isPalindrome';
    }
    
    protected function handler(mixed $value): bool
    {
        if (!is_string($value)) return false;
        return $value === strrev($value);
    }
}

$data1 = new stdClass();
$data1->word = 'hello';

$dv1 = new DataVerify($data1);
$dv1->registerStrategy(new IsPalindromeStrategy());

$dv1->addTranslations([
    'validation.isPalindrome' => 'The field {field} must be a palindrome (reads same forwards and backwards)'
], 'en');

$dv1->field('word')->isPalindrome();

if (!$dv1->verify()) {
    echo "   Error: " . $dv1->getErrors()[0]['message'] . "\n";
}
echo "\n";

// Example 2: Custom translations in multiple languages
echo "2. Custom validation in French\n";

$data2 = new stdClass();
$data2->mot = 'bonjour';

$dv2 = new DataVerify($data2);
$dv2->registerStrategy(new IsPalindromeStrategy());

$dv2->addTranslations([
    'validation.isPalindrome' => 'Le champ {field} doit être un palindrome'
], 'fr');

$dv2->setLocale('fr');
$dv2->field('mot')->isPalindrome();

if (!$dv2->verify()) {
    echo "   Erreur: " . $dv2->getErrors()[0]['message'] . "\n";
}
echo "\n";

// Example 3: Loading custom translations from external file
echo "3. Loading custom translations from file\n";

$tmpFile = sys_get_temp_dir() . '/my-custom-validations.php';
file_put_contents($tmpFile, <<<'PHP'
<?php
return [
    'validation.isValidSIRET' => 'The field {field} must be a valid SIRET number (14 digits)',
    'validation.isValidIBAN' => 'The field {field} must be a valid IBAN',
];
PHP
);

$dv3 = new DataVerify(new stdClass());
$dv3->loadLocale('en', $tmpFile);

echo "   ✓ Custom translations loaded from: {$tmpFile}\n";
@unlink($tmpFile);
echo "\n";

// Example 4: Combining default + custom translations
echo "4. Combining default and custom translations\n";

$data4 = new stdClass();
$data4->email = '';
$data4->code = 'invalid';

$dv4 = new DataVerify($data4);
$dv4->field('email')->required->email;

$dv4->addTranslations([
    'validation.customCode' => 'The {field} must match our internal code format'
], 'en');

if (!$dv4->verify()) {
    foreach ($dv4->getErrors() as $error) {
        echo "   Error: " . $error['message'] . "\n";
    }
}
echo "\n";

echo "=== End ===\n";