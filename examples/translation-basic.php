<?php

require __DIR__ . '/../vendor/autoload.php';

use Gravity\DataVerify;

echo "=== Translation - Basic Usage ===\n\n";

// Example 1: Default English messages
echo "1. Default English messages\n";
$data1 = new stdClass();
$data1->email = '';

$dv1 = new DataVerify($data1);
$dv1->field('email')->required->email;

if (!$dv1->verify()) {
    echo "   Error: " . $dv1->getErrors()[0]['message'] . "\n";
    // Output: The field email is required
}
echo "\n";

// Example 2: French locale
echo "2. Using French locale\n";
$data2 = new stdClass();
$data2->email = 'invalid';

$dv2 = new DataVerify($data2);
$dv2->loadLocale('fr');
$dv2->setLocale('fr');
$dv2->field('email')->required->email;

if (!$dv2->verify()) {
    echo "   Erreur: " . $dv2->getErrors()[0]['message'] . "\n";
    // Output: Le champ email doit être une adresse email valide
}
echo "\n";

// Example 3: Messages with placeholders
echo "3. Messages with placeholders\n";
$data3 = new stdClass();
$data3->password = 'abc';

$dv3 = new DataVerify($data3);
$dv3->field('password')->required->minLength(8);

if (!$dv3->verify()) {
    echo "   Error: " . $dv3->getErrors()[0]['message'] . "\n";
    // Output: The field password must be at least 8 characters
}
echo "\n";

// Example 4: Using alias with translations
echo "4. Field alias with translations\n";
$data4 = new stdClass();
$data4->user_email = '';

$dv4 = new DataVerify($data4);
$dv4->field('user_email')->alias('Email Address')->required;

if (!$dv4->verify()) {
    echo "   Error: " . $dv4->getErrors()[0]['message'] . "\n";
    // Output: The field Email Address is required
}
echo "\n";

echo "=== End ===\n";