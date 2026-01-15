<?php

require __DIR__ . '/../vendor/autoload.php';

use Gravity\DataVerify;

echo "=== Schema Validation Examples ===\n\n";

// Example 1: Basic schema with simple fields
echo "1. Basic schema registration and usage\n";

DataVerify::registerSchema('contactForm')
    ->field('name')->required->string->minLength(2)
    ->field('email')->required->email
    ->field('phone')->string->regex('/^\+?[1-9]\d{1,14}$/');

$data1 = new stdClass();
$data1->name = 'John Doe';
$data1->email = 'john@example.com';
$data1->phone = '+33612345678';

$dv1 = new DataVerify($data1);
$dv1->schema('contactForm');

if ($dv1->verify()) {
    echo "   ✓ Contact form validation passed\n";
} else {
    echo "   ✗ Contact form validation failed:\n";
    foreach ($dv1->getErrors() as $error) {
        echo "     - {$error['field']}: {$error['message']}\n";
    }
}
echo "\n";

// Example 2: Three syntaxes for applying schemas
echo "2. Three syntaxes for applying schemas\n";

DataVerify::registerSchema('simpleUser')
    ->field('username')->required->alphanumeric->minLength(3)
    ->field('email')->required->email;

$data2 = new stdClass();
$data2->username = 'john123';
$data2->email = 'john@example.com';

// Syntax 1: Method call
$dv2a = new DataVerify($data2);
$dv2a->schema('simpleUser');

// Syntax 2: Property chain
$dv2b = new DataVerify($data2);
$dv2b->schema->simpleUser;

// Syntax 3: Property with method call
$dv2c = new DataVerify($data2);
$dv2c->schema->simpleUser();

if ($dv2a->verify() && $dv2b->verify() && $dv2c->verify()) {
    echo "   ✓ All three syntaxes work identically\n";
}
echo "\n";

// Example 3: Nested objects with subfields
echo "3. Schema with nested objects\n";

DataVerify::registerSchema('userProfile')
    ->field('user')->required->object
        ->subfield('name')->required->string->minLength(2)
        ->subfield('email')->required->email
        ->subfield('profile')->required->object
            ->subfield('profile', 'age')->required->int->greaterThan(18)
            ->subfield('profile', 'country')->required->string->minLength(2);

$data3 = new stdClass();
$data3->user = new stdClass();
$data3->user->name = 'John Doe';
$data3->user->email = 'john@example.com';
$data3->user->profile = new stdClass();
$data3->user->profile->age = 25;
$data3->user->profile->country = 'FR';

$dv3 = new DataVerify($data3);
$dv3->schema('userProfile');

if ($dv3->verify()) {
    echo "   ✓ Nested object validation passed\n";
} else {
    echo "   ✗ Nested object validation failed\n";
}
echo "\n";

// Example 4: Schema with conditional validation (POST vs PATCH)
echo "4. Schema with conditional validation - API endpoint\n";

DataVerify::registerSchema('userApi')
    ->field('user')->required->object
        ->subfield('id')->int
        ->subfield('email')
            ->email
            ->disposableEmail
            ->when('user.id', '!=', null)
                ->then->required
        ->subfield('password')
            ->minLength(12)
            ->containsUpper
            ->containsLower
            ->containsSpecialCharacter
            ->when('user.id', '!=', null)
                ->then->required
        ->subfield('name')
            ->string
            ->minLength(2)
            ->when('user.id', '!=', null)
                ->then->required;

// POST scenario (id = null) - fields optional
$postData = new stdClass();
$postData->user = new stdClass();
$postData->user->id = null;
$postData->user->name = 'John';

$dvPost = new DataVerify($postData);
$dvPost->schema('userApi');

if ($dvPost->verify()) {
    echo "   ✓ POST validation passed (fields optional when id=null)\n";
}

// PATCH scenario (id exists) - fields required
$patchData = new stdClass();
$patchData->user = new stdClass();
$patchData->user->id = 123;
$patchData->user->email = '';

$dvPatch = new DataVerify($patchData);
$dvPatch->schema('userApi');

if (!$dvPatch->verify()) {
    echo "   ✗ PATCH validation failed (fields required when id exists):\n";
    foreach ($dvPatch->getErrors() as $error) {
        echo "     - {$error['field']}: {$error['test']}\n";
    }
}
echo "\n";

// Example 5: Schema using registered rules
echo "5. Schema composed with reusable rules\n";

DataVerify::registerRules('strongPassword')
    ->minLength(12)
    ->containsUpper
    ->containsLower
    ->containsNumber
    ->containsSpecialCharacter;

DataVerify::registerRules('validEmail')
    ->email
    ->disposableEmail;

DataVerify::registerSchema('userRegistration')
    ->field('user')->required->object
        ->subfield('email')->required->rule('validEmail')
        ->subfield('password')->required->rule('strongPassword')
        ->subfield('username')->required->alphanumeric->minLength(3)->maxLength(20);

$data5 = new stdClass();
$data5->user = new stdClass();
$data5->user->email = 'user@example.com';
$data5->user->password = 'SecureP@ss123';
$data5->user->username = 'john_doe';

$dv5 = new DataVerify($data5);
$dv5->schema('userRegistration');

if ($dv5->verify()) {
    echo "   ✓ User registration with composed rules passed\n";
} else {
    echo "   ✗ User registration failed\n";
}
echo "\n";

// Example 6: Multi-step form validation
echo "6. Multi-step form with separate schemas\n";

// Step 1: Basic information
DataVerify::registerSchema('registrationStep1')
    ->field('email')->required->email
    ->field('password')->required->minLength(12);

// Step 2: Profile details
DataVerify::registerSchema('registrationStep2')
    ->field('name')->required->string->minLength(2)
    ->field('birthdate')->required->date;

// Step 3: Address
DataVerify::registerSchema('registrationStep3')
    ->field('address')->required->string->minLength(5)
    ->field('city')->required->string
    ->field('zipcode')->required->regex('/^\d{5}$/');

$step1Data = new stdClass();
$step1Data->email = 'user@example.com';
$step1Data->password = 'SecurePass123';

$step2Data = new stdClass();
$step2Data->name = 'John Doe';
$step2Data->birthdate = '1990-01-15';

$step3Data = new stdClass();
$step3Data->address = '123 Main Street';
$step3Data->city = 'Paris';
$step3Data->zipcode = '75001';

$dvStep1 = new DataVerify($step1Data);
$dvStep1->schema('registrationStep1');

$dvStep2 = new DataVerify($step2Data);
$dvStep2->schema('registrationStep2');

$dvStep3 = new DataVerify($step3Data);
$dvStep3->schema('registrationStep3');

if ($dvStep1->verify() && $dvStep2->verify() && $dvStep3->verify()) {
    echo "   ✓ All registration steps validated successfully\n";
}
echo "\n";

// Example 7: Complex nested structure with arrays
echo "7. Schema with nested arrays and objects\n";

DataVerify::registerSchema('orderWithItems')
    ->field('order')->required->object
        ->subfield('customer')->required->object
            ->subfield('customer', 'name')->required->string
            ->subfield('customer', 'email')->required->email
        ->subfield('items')->required->array
        ->subfield('shipping')->required->object
            ->subfield('shipping', 'address')->required->string->minLength(5)
            ->subfield('shipping', 'city')->required->string
            ->subfield('shipping', 'zipcode')->required->regex('/^\d{5}$/');

$data7 = new stdClass();
$data7->order = new stdClass();
$data7->order->customer = new stdClass();
$data7->order->customer->name = 'John Doe';
$data7->order->customer->email = 'john@example.com';
$data7->order->items = [
    ['product_id' => 1, 'quantity' => 2],
    ['product_id' => 2, 'quantity' => 1]
];
$data7->order->shipping = new stdClass();
$data7->order->shipping->address = '123 Main Street';
$data7->order->shipping->city = 'Paris';
$data7->order->shipping->zipcode = '75001';

$dv7 = new DataVerify($data7);
$dv7->schema('orderWithItems');

if ($dv7->verify()) {
    echo "   ✓ Complex order structure validated\n";
} else {
    echo "   ✗ Order validation failed\n";
}
echo "\n";

// Example 8: Conditional validation with multiple conditions
echo "8. Schema with complex conditional logic\n";

DataVerify::registerSchema('paymentForm')
    ->field('payment_method')->required->string
    ->field('card_number')
        ->when('payment_method', '=', 'credit_card')
            ->then->required->regex('/^\d{16}$/')
    ->field('card_cvv')
        ->when('payment_method', '=', 'credit_card')
            ->then->required->regex('/^\d{3,4}$/')
    ->field('paypal_email')
        ->when('payment_method', '=', 'paypal')
            ->then->required->email
    ->field('bank_account')
        ->when('payment_method', '=', 'bank_transfer')
            ->then->required->regex('/^[A-Z]{2}\d{2}[A-Z0-9]+$/');

// Credit card payment
$data8a = new stdClass();
$data8a->payment_method = 'credit_card';
$data8a->card_number = '1234567812345678';
$data8a->card_cvv = '123';

$dv8a = new DataVerify($data8a);
$dv8a->schema('paymentForm');

if ($dv8a->verify()) {
    echo "   ✓ Credit card payment validated\n";
}

// PayPal payment
$data8b = new stdClass();
$data8b->payment_method = 'paypal';
$data8b->paypal_email = 'user@paypal.com';

$dv8b = new DataVerify($data8b);
$dv8b->schema('paymentForm');

if ($dv8b->verify()) {
    echo "   ✓ PayPal payment validated\n";
}
echo "\n";

// Example 9: Schema for form with optional sections
echo "9. Form with optional sections based on conditions\n";

DataVerify::registerSchema('shippingForm')
    ->field('delivery_type')->required->string
    ->field('billing_address')->required->object
        ->subfield('billing_address', 'street')->required->string
        ->subfield('billing_address', 'city')->required->string
        ->subfield('billing_address', 'zipcode')->required->regex('/^\d{5}$/')
    ->field('shipping_address')->object
        ->subfield('shipping_address', 'street')
            ->when('delivery_type', '=', 'shipping')
                ->then->required->string
        ->subfield('shipping_address', 'city')
            ->when('delivery_type', '=', 'shipping')
                ->then->required->string
        ->subfield('shipping_address', 'zipcode')
            ->when('delivery_type', '=', 'shipping')
                ->then->required->regex('/^\d{5}$/');

// Pickup - no shipping address needed
$data9a = new stdClass();
$data9a->delivery_type = 'pickup';
$data9a->billing_address = new stdClass();
$data9a->billing_address->street = '123 Main St';
$data9a->billing_address->city = 'Paris';
$data9a->billing_address->zipcode = '75001';

$dv9a = new DataVerify($data9a);
$dv9a->schema('shippingForm');

if ($dv9a->verify()) {
    echo "   ✓ Pickup order validated (no shipping address)\n";
}

// Shipping - shipping address required
$data9b = new stdClass();
$data9b->delivery_type = 'shipping';
$data9b->billing_address = new stdClass();
$data9b->billing_address->street = '123 Main St';
$data9b->billing_address->city = 'Paris';
$data9b->billing_address->zipcode = '75001';
$data9b->shipping_address = new stdClass();
$data9b->shipping_address->street = '456 Other St';
$data9b->shipping_address->city = 'Lyon';
$data9b->shipping_address->zipcode = '69001';

$dv9b = new DataVerify($data9b);
$dv9b->schema('shippingForm');

if ($dv9b->verify()) {
    echo "   ✓ Shipping order validated (with shipping address)\n";
}
echo "\n";

// Example 10: Business validation schema with rules
echo "10. Business-specific validation schema\n";

DataVerify::registerRules('frenchPhone')
    ->regex('/^(?:\+33|0)[1-9](?:\d{8})$/');

DataVerify::registerRules('siret')
    ->regex('/^\d{14}$/')
    ->minLength(14)
    ->maxLength(14);

DataVerify::registerSchema('frenchCompany')
    ->field('company')->required->object
        ->subfield('name')->required->string->minLength(2)
        ->subfield('siret')->required->rule('siret')
        ->subfield('email')->required->email
        ->subfield('phone')->required->rule('frenchPhone')
        ->subfield('address')->required->object
            ->subfield('address', 'street')->required->string
            ->subfield('address', 'city')->required->string
            ->subfield('address', 'zipcode')->required->regex('/^\d{5}$/');

$data10 = new stdClass();
$data10->company = new stdClass();
$data10->company->name = 'ACME France';
$data10->company->siret = '12345678901234';
$data10->company->email = 'contact@acme.fr';
$data10->company->phone = '+33612345678';
$data10->company->address = new stdClass();
$data10->company->address->street = '123 Rue de la Paix';
$data10->company->address->city = 'Paris';
$data10->company->address->zipcode = '75001';

$dv10 = new DataVerify($data10);
$dv10->schema('frenchCompany');

if ($dv10->verify()) {
    echo "   ✓ French company data validated\n";
} else {
    echo "   ✗ French company validation failed\n";
}
echo "\n";

echo "=== End of Schema examples ===\n";

// Example 11: Schema loading performance
echo "11. Batch schema registration at application startup\n";

$startTime = microtime(true);

// Typical API application might register multiple schemas
DataVerify::registerSchema('apiUser')
    ->field('email')->required->email
    ->field('password')->required->minLength(12);

DataVerify::registerSchema('apiProduct')
    ->field('name')->required->string->minLength(3)
    ->field('price')->required->numeric->greaterThan(0);

DataVerify::registerSchema('apiOrder')
    ->field('order')->required->object
        ->subfield('id')->required->int
        ->subfield('items')->required->array;

DataVerify::registerSchema('apiPayment')
    ->field('amount')->required->numeric->greaterThan(0)
    ->field('currency')->required->string->minLength(3)->maxLength(3);

DataVerify::registerSchema('apiShipping')
    ->field('address')->required->string->minLength(5)
    ->field('city')->required->string
    ->field('country')->required->string->minLength(2)->maxLength(2);

$endTime = microtime(true);
$duration = ($endTime - $startTime) * 1000;

echo "   ✓ Registered 5 API schemas in " . number_format($duration, 3) . "ms\n";
echo "   (Schemas are cached and reusable across all requests)\n";
echo "\n";

// Example 12: Schema reusability across multiple requests
echo "12. Schema reuse across multiple validation calls\n";

DataVerify::registerSchema('quickCheck')
    ->field('email')->required->email
    ->field('name')->required->string;

// Simulate 5 different API requests using same schema
$requests = [
    ['email' => 'user1@example.com', 'name' => 'User One'],
    ['email' => 'user2@example.com', 'name' => 'User Two'],
    ['email' => 'invalid-email', 'name' => 'User Three'],
    ['email' => 'user4@example.com', 'name' => 'U'], // Too short
    ['email' => 'user5@example.com', 'name' => 'User Five'],
];

$validCount = 0;
foreach ($requests as $idx => $requestData) {
    $data = new stdClass();
    $data->email = $requestData['email'];
    $data->name = $requestData['name'];
    
    $dv = new DataVerify($data);
    $dv->schema('quickCheck');
    
    if ($dv->verify()) {
        $validCount++;
    }
}

echo "   ✓ Processed {$validCount}/" . count($requests) . " valid requests using single schema\n";
echo "\n";

// Example 13: Dynamic schema composition
echo "13. Dynamic schema composition for microservices\n";

function getSchemaForService(string $serviceName): string {
    $schemaName = "service_{$serviceName}";
    
    if ($serviceName === 'auth') {
        DataVerify::registerSchema($schemaName)
            ->field('username')->required->alphanumeric->minLength(3)
            ->field('password')->required->minLength(12)
            ->field('email')->required->email;
    } elseif ($serviceName === 'catalog') {
        DataVerify::registerSchema($schemaName)
            ->field('product')->required->object
                ->subfield('name')->required->string
                ->subfield('price')->required->numeric->greaterThan(0)
                ->subfield('stock')->required->int->greaterThan(-1);
    } elseif ($serviceName === 'notification') {
        DataVerify::registerSchema($schemaName)
            ->field('recipient')->required->email
            ->field('subject')->required->string->minLength(5)
            ->field('body')->required->string->minLength(10);
    }
    
    return $schemaName;
}

// Auth service validation
$authData = new stdClass();
$authData->username = 'john123';
$authData->password = 'SecurePass123';
$authData->email = 'john@example.com';

$dvAuth = new DataVerify($authData);
$dvAuth->schema(getSchemaForService('auth'));

if ($dvAuth->verify()) {
    echo "   ✓ Auth service: user registration valid\n";
}

// Catalog service validation
$catalogData = new stdClass();
$catalogData->product = new stdClass();
$catalogData->product->name = 'Laptop';
$catalogData->product->price = 999.99;
$catalogData->product->stock = 50;

$dvCatalog = new DataVerify($catalogData);
$dvCatalog->schema(getSchemaForService('catalog'));

if ($dvCatalog->verify()) {
    echo "   ✓ Catalog service: product data valid\n";
}

// Notification service validation
$notifData = new stdClass();
$notifData->recipient = 'user@example.com';
$notifData->subject = 'Order Confirmation';
$notifData->body = 'Your order has been confirmed and will be shipped soon.';

$dvNotif = new DataVerify($notifData);
$dvNotif->schema(getSchemaForService('notification'));

if ($dvNotif->verify()) {
    echo "   ✓ Notification service: message valid\n";
}
echo "\n";

echo "=== End of Schema examples ===\n";