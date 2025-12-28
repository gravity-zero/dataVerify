<?php
require __DIR__ . '/../vendor/autoload.php';

use Gravity\DataVerify;

$data = new stdClass();
$data->email = $_POST['email'] ?? 'test@example.com';
$data->age = $_POST['age'] ?? 25;

$verifier = new DataVerify($data);
$verifier
    ->field('email')->required->email
    ->field('age')->required->int->between(18, 100);

if ($verifier->verify()) {
    echo "✓ Validation passed!\n";
} else {
    print_r($verifier->getErrors());
}