<?php

require __DIR__ . '/../vendor/autoload.php';

use Gravity\DataVerify;

echo "=== Reusable Validation Rules Examples ===\n\n";

// Example 1: Basic rule registration and usage
echo "1. Register and use a simple rule\n";

DataVerify::registerRules('strongPassword')
    ->minLength(12)
    ->containsUpper
    ->containsLower
    ->containsNumber
    ->containsSpecialCharacter;

$data1 = new stdClass();
$data1->password = 'WeakPass';

$dv1 = new DataVerify($data1);
$dv1->field('password')->required->rule('strongPassword');

if (!$dv1->verify()) {
    echo "   ✗ Password validation failed:\n";
    foreach ($dv1->getErrors() as $error) {
        echo "     - {$error['test']}: {$error['message']}\n";
    }
} else {
    echo "   ✓ Validation passed\n";
}
echo "\n";

// Example 2: Multiple syntaxes for applying rules
echo "2. Three syntaxes for applying rules\n";

DataVerify::registerRules('validEmail')
    ->email
    ->disposableEmail;

$data2 = new stdClass();
$data2->email1 = 'test@example.com';
$data2->email2 = 'test@tempmail.com';
$data2->email3 = 'test@guerrillamail.com';

$dv2 = new DataVerify($data2);

// Syntax 1: Method call
$dv2->field('email1')->rule('validEmail');

// Syntax 2: Property chain
$dv2->field('email2')->rule->validEmail;

// Syntax 3: Property with method call (same as syntax 2)
$dv2->field('email3')->rule->validEmail();

if (!$dv2->verify()) {
    echo "   ✗ Some emails are invalid or disposable:\n";
    foreach ($dv2->getErrors() as $error) {
        echo "     - {$error['field']}\n";
    }
} else {
    echo "   ✓ All emails valid\n";
}
echo "\n";

// Example 3: Chaining multiple rules
echo "3. Chain multiple rules on same field\n";

DataVerify::registerRules('basicPassword')
    ->minLength(8)
    ->containsUpper;

DataVerify::registerRules('securePassword')
    ->containsNumber
    ->containsSpecialCharacter;

$data3 = new stdClass();
$data3->password = 'MyP@ss123';

$dv3 = new DataVerify($data3);
$dv3->field('password')
    ->required
    ->rule('basicPassword')
    ->rule('securePassword');

if ($dv3->verify()) {
    echo "   ✓ Password meets all security requirements\n";
} else {
    echo "   ✗ Password validation failed\n";
}
echo "\n";

// Example 4: Rules for different contexts
echo "4. Context-specific rules\n";

// Development environment - relaxed rules
DataVerify::registerRules('devPassword')
    ->minLength(6);

// Production environment - strict rules
DataVerify::registerRules('prodPassword')
    ->minLength(12)
    ->containsUpper
    ->containsLower
    ->containsNumber
    ->containsSpecialCharacter;

$env = 'production'; // or 'development'
$ruleName = $env === 'production' ? 'prodPassword' : 'devPassword';

$data4 = new stdClass();
$data4->password = 'Pass123!';

$dv4 = new DataVerify($data4);
$dv4->field('password')->required->rule($ruleName);

echo "   Environment: {$env}\n";
if ($dv4->verify()) {
    echo "   ✓ Password valid for {$env}\n";
} else {
    echo "   ✗ Password too weak for {$env}\n";
}
echo "\n";

// Example 5: Reusable business rules
echo "5. Business-specific validation rules\n";

DataVerify::registerRules('frenchPhone')
    ->regex('/^(?:\+33|0)[1-9](?:\d{8})$/');

DataVerify::registerRules('frenchPostalCode')
    ->regex('/^\d{5}$/');

DataVerify::registerRules('siret')
    ->regex('/^\d{14}$/')
    ->minLength(14)
    ->maxLength(14);

$data5 = new stdClass();
$data5->phone = '+33612345678';
$data5->postal_code = '75001';
$data5->siret = '12345678901234';

$dv5 = new DataVerify($data5);
$dv5
    ->field('phone')->required->rule('frenchPhone')
    ->field('postal_code')->required->rule('frenchPostalCode')
    ->field('siret')->required->rule('siret');

if ($dv5->verify()) {
    echo "   ✓ All French business identifiers valid\n";
} else {
    echo "   ✗ Validation failed\n";
}
echo "\n";

// Example 6: Rules for data quality
echo "6. Data quality rules\n";

DataVerify::registerRules('cleanString')
    ->string
    ->minLength(1)
    ->maxLength(255)
    ->notAlphanumeric; // Contains more than just letters/numbers

DataVerify::registerRules('positiveNumber')
    ->numeric
    ->greaterThan(0);

DataVerify::registerRules('percentage')
    ->numeric
    ->between(0, 100);

$data6 = new stdClass();
$data6->name = 'John Doe';
$data6->price = 99.99;
$data6->discount = 15;

$dv6 = new DataVerify($data6);
$dv6
    ->field('name')->required->rule('cleanString')
    ->field('price')->required->rule('positiveNumber')
    ->field('discount')->required->rule('percentage');

if ($dv6->verify()) {
    echo "   ✓ Data quality validation passed\n";
} else {
    echo "   ✗ Data quality issues detected\n";
}
echo "\n";

// Example 7: Rules with inline validations
echo "7. Mix rules with inline validations\n";

DataVerify::registerRules('username')
    ->alphanumeric
    ->minLength(3)
    ->maxLength(20);

$data7 = new stdClass();
$data7->username = 'john_doe';
$data7->email = 'john@example.com';

$dv7 = new DataVerify($data7);
$dv7
    ->field('username')
        ->required
        ->rule('username')
        ->regex('/^[a-z0-9_]+$/') // Additional inline validation
    ->field('email')
        ->required
        ->email;

if ($dv7->verify()) {
    echo "   ✓ User registration valid\n";
} else {
    echo "   ✗ User registration failed:\n";
    foreach ($dv7->getErrors() as $error) {
        echo "     - {$error['field']}: {$error['message']}\n";
    }
}
echo "\n";

// Example 8: Rules for file validation
echo "8. File validation rules\n";

DataVerify::registerRules('imageFile')
    ->fileMime(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

DataVerify::registerRules('documentFile')
    ->fileMime(['application/pdf', 'application/msword', 'text/plain']);

// Simulating uploaded files
$data8 = new stdClass();
$data8->avatar = __DIR__ . '/../README.md'; // Exists
$data8->resume = __DIR__ . '/../composer.json'; // Exists

$dv8 = new DataVerify($data8);
$dv8
    ->field('avatar')->required->fileExists->rule('imageFile')
    ->field('resume')->required->fileExists->rule('documentFile');

if (!$dv8->verify()) {
    echo "   ✗ File validation failed (expected - using wrong file types)\n";
} else {
    echo "   ✓ File validation passed\n";
}
echo "\n";

// Example 9: Rules for API validation
echo "9. API validation rules\n";

DataVerify::registerRules('apiEmail')
    ->email
    ->disposableEmail
    ->maxLength(255);

DataVerify::registerRules('apiPassword')
    ->minLength(12)
    ->maxLength(128)
    ->containsUpper
    ->containsLower
    ->containsNumber;

DataVerify::registerRules('apiUsername')
    ->alphanumeric
    ->minLength(3)
    ->maxLength(30);

$apiData = new stdClass();
$apiData->email = 'user@example.com';
$apiData->password = 'SecurePass123';
$apiData->username = 'john_doe_2024';

$dvApi = new DataVerify($apiData);
$dvApi
    ->field('email')->required->rule('apiEmail')
    ->field('password')->required->rule('apiPassword')
    ->field('username')->required->rule('apiUsername');

if ($dvApi->verify()) {
    echo "   ✓ API user registration valid\n";
} else {
    echo "   ✗ API validation failed\n";
}
echo "\n";

// Example 10: Rules composition for complex scenarios
echo "10. Complex rules composition\n";

DataVerify::registerRules('baseUser')
    ->string
    ->minLength(2)
    ->maxLength(100);

DataVerify::registerRules('premiumUser')
    ->minLength(5); // Stricter requirements

DataVerify::registerRules('adminUser')
    ->minLength(8)
    ->regex('/^[A-Z]/'); // Must start with uppercase

$data10 = new stdClass();
$data10->user_type = 'admin';
$data10->name = 'Admin User';

$dv10 = new DataVerify($data10);
$dv10->field('name')
    ->required
    ->rule('baseUser')
    ->rule('adminUser'); // Admin users get additional validation

if ($dv10->verify()) {
    echo "   ✓ Admin user name valid\n";
} else {
    echo "   ✗ Admin user name validation failed\n";
}
echo "\n";

echo "=== End of Rules examples ===\n";

// Example 11: Batch registration for performance
echo "11. Batch registration of rules at startup\n";

// In a real application, you might register all rules at bootstrap
$startTime = microtime(true);

for ($i = 1; $i <= 10; $i++) {
    DataVerify::registerRules("appRule{$i}")
        ->minLength(3)
        ->maxLength(100);
}

$endTime = microtime(true);
$duration = ($endTime - $startTime) * 1000;

echo "   ✓ Registered 10 rules in " . number_format($duration, 3) . "ms\n";
echo "   (Rules are cached and reusable across requests)\n";
echo "\n";

// Example 12: Dynamic rule composition
echo "12. Dynamic rule composition based on user tier\n";

function registerTierRules(string $tier): string {
    $ruleName = "tier{$tier}Password";
    
    $builder = DataVerify::registerRules($ruleName)
        ->minLength(8);
    
    if ($tier === 'premium') {
        $builder->minLength(12)->containsUpper->containsLower->containsNumber;
    }
    
    if ($tier === 'enterprise') {
        $builder->minLength(16)
            ->containsUpper
            ->containsLower
            ->containsNumber
            ->containsSpecialCharacter;
    }
    
    return $ruleName;
}

$userTier = 'enterprise';
$ruleName = registerTierRules($userTier);

$data12 = new stdClass();
$data12->password = 'EnterpriseP@ss123456';

$dv12 = new DataVerify($data12);
$dv12->field('password')->required->rule($ruleName);

if ($dv12->verify()) {
    echo "   ✓ {$userTier} tier password requirements met\n";
} else {
    echo "   ✗ {$userTier} tier password requirements not met\n";
}
echo "\n";

// Example 13: Rule reusability across multiple datasets
echo "13. Rule reusability across multiple datasets\n";

DataVerify::registerRules('standardEmail')
    ->email
    ->disposableEmail
    ->maxLength(320); // RFC 5321

$datasets = [
    ['email' => 'valid@example.com', 'expected' => true],
    ['email' => 'temp@tempmail.com', 'expected' => false],
    ['email' => 'toolong' . str_repeat('x', 320) . '@example.com', 'expected' => false],
];

echo "   Testing rule across " . count($datasets) . " datasets:\n";
foreach ($datasets as $idx => $testCase) {
    $testData = new stdClass();
    $testData->email = $testCase['email'];
    
    $dvTest = new DataVerify($testData);
    $dvTest->field('email')->required->rule('standardEmail');
    
    $result = $dvTest->verify();
    $status = ($result === $testCase['expected']) ? '✓' : '✗';
    echo "     {$status} Dataset " . ($idx + 1) . ": " . ($result ? 'valid' : 'invalid') . "\n";
}
echo "\n";

echo "=== End of Rules examples ===\n";