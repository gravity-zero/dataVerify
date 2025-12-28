<?php

require __DIR__ . '/../vendor/autoload.php';

use Gravity\DataVerify;

echo "=== Translation - YAML Support ===\n\n";

if (!class_exists('\Symfony\Component\Yaml\Yaml')) {
    echo "⚠️  symfony/yaml is not installed.\n";
    echo "   Install it with: composer require symfony/yaml\n\n";
    exit(0);
}

echo "✓ symfony/yaml is installed\n\n";

// Example: Using YAML translation file
echo "1. Loading YAML translation file\n";

$yamlContent = <<<YAML
validation.required: "Das Feld {field} ist erforderlich"
validation.email: "Das Feld {field} muss eine gültige E-Mail-Adresse sein"
validation.minLength: "Das Feld {field} muss mindestens {min} Zeichen lang sein"
YAML;

$tmpFile = sys_get_temp_dir() . '/de.yaml';
file_put_contents($tmpFile, $yamlContent);

$data = new stdClass();
$data->email = '';

$dv = new DataVerify($data);
$dv->loadLocale('de', $tmpFile);
$dv->setLocale('de');
$dv->field('email')->required->email;

if (!$dv->verify()) {
    echo "   Fehler: " . $dv->getErrors()[0]['message'] . "\n";
    // Output: Das Feld email ist erforderlich
}

@unlink($tmpFile);
echo "\n";

echo "Note: YAML files are optional. PHP files work without any dependencies.\n";
echo "=== End ===\n";