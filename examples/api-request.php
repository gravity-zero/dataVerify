<?php
require __DIR__ . '/../vendor/autoload.php';

use Gravity\DataVerify;

$json = '{"user":{"name":"John","email":"john@example.com","profile":{"age":30}}}';
$data = json_decode($json);

$verifier = new DataVerify($data);
$verifier
    ->field('user')->required->object
        ->subfield('name')->required->string->minLength(2)
        ->subfield('email')->required->email
        ->subfield('profile')->required->object
            ->subfield('profile', 'age')->required->int->greaterThan(18);

if (!$verifier->verify()) {
    http_response_code(400);
    echo json_encode(['errors' => $verifier->getErrors()]);
}