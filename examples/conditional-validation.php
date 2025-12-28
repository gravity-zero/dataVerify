<?php

require __DIR__ . '/../vendor/autoload.php';

use Gravity\DataVerify;

echo "=== Conditional Validation Examples ===\n\n";

// Example 1: Basic conditional - shipping address required only if delivery is shipping
echo "1. Basic conditional validation\n";
$data1 = new stdClass();
$data1->delivery_type = 'shipping';
$data1->shipping_address = '';

$dv1 = new DataVerify($data1);
$dv1
    ->field('shipping_address')
    ->when('delivery_type', '=', 'shipping')
    ->then->required->string;

if (!$dv1->verify()) {
    echo "   ✗ Validation failed:\n";
    foreach ($dv1->getErrors() as $error) {
        echo "     - {$error['message']}\n";
    }
} else {
    echo "   ✓ Validation passed\n";
}
echo "\n";

// Example 2: AND conditions - all must be true
echo "2. AND conditions (all must be true)\n";
$data2 = new stdClass();
$data2->type = 'premium';
$data2->amount = 150;
$data2->discount_code = '';

$dv2 = new DataVerify($data2);
$dv2
    ->field('discount_code')
    ->when('type', '=', 'premium')
    ->and('amount', '>', 100)
    ->then->required->string;

if (!$dv2->verify()) {
    echo "   ✗ Discount code required for premium orders over 100€\n";
} else {
    echo "   ✓ Validation passed\n";
}
echo "\n";

// Example 3: OR conditions - at least one must be true
echo "3. OR conditions (at least one must be true)\n";
$data3 = new stdClass();
$data3->country = 'BE';
$data3->vat_number = '';

$dv3 = new DataVerify($data3);
$dv3
    ->field('vat_number')
    ->when('country', '=', 'FR')
    ->or('country', '=', 'BE')
    ->or('country', '=', 'DE')
    ->then->required->regex('/^[A-Z]{2}\d{9,11}$/');

if (!$dv3->verify()) {
    echo "   ✗ VAT number required for FR, BE, or DE\n";
} else {
    echo "   ✓ Validation passed\n";
}
echo "\n";

// Example 4: Complex AND with multiple conditions
echo "4. Complex AND with multiple conditions\n";
$data4 = new stdClass();
$data4->age = 25;
$data4->country = 'FR';
$data4->income = 50000;
$data4->premium_feature = '';

$dv4 = new DataVerify($data4);
$dv4
    ->field('premium_feature')
    ->when('age', '>=', 18)
    ->and('country', 'in', ['FR', 'BE'])
    ->and('income', '>', 30000)
    ->then->required;

if (!$dv4->verify()) {
    echo "   ✗ Premium feature access denied\n";
} else {
    echo "   ✓ Premium feature validation passed\n";
}
echo "\n";

// Example 5: Numeric comparison - parental consent for minors
echo "5. Age-based conditional validation\n";
$data5 = new stdClass();
$data5->age = 15;
$data5->parental_consent = null;

$dv5 = new DataVerify($data5);
$dv5
    ->field('parental_consent')
    ->when('age', '<', 18)
    ->then->required->boolean;

if (!$dv5->verify()) {
    echo "   ✗ Parental consent required for users under 18\n";
} else {
    echo "   ✓ Validation passed\n";
}
echo "\n";

// Example 6: Multiple conditional validations on different fields
echo "6. Multiple conditional validations\n";
$data6 = new stdClass();
$data6->account_type = 'business';
$data6->has_vat = true;
$data6->company_name = '';
$data6->vat_number = '';

$dv6 = new DataVerify($data6);
$dv6
    ->field('company_name')
    ->when('account_type', '=', 'business')
    ->then->required->string
    
    ->field('vat_number')
    ->when('has_vat', '=', true)
    ->then->required->string;

if (!$dv6->verify()) {
    echo "   ✗ Business account validation failed:\n";
    foreach ($dv6->getErrors() as $error) {
        echo "     - {$error['field']}: {$error['message']}\n";
    }
} else {
    echo "   ✓ Validation passed\n";
}
echo "\n";

// Example 7: Mix normal and conditional validations
echo "7. Mix normal and conditional validations\n";
$data7 = new stdClass();
$data7->email = 'invalid-email';
$data7->newsletter = true;
$data7->phone = '';

$dv7 = new DataVerify($data7);
$dv7
    ->field('email')
    ->required
    ->email
    
    ->field('phone')
    ->when('newsletter', '=', true)
    ->then->required;

if (!$dv7->verify()) {
    echo "   ✗ Validation errors:\n";
    foreach ($dv7->getErrors() as $error) {
        echo "     - {$error['field']}: {$error['test']} failed\n";
    }
} else {
    echo "   ✓ Validation passed\n";
}
echo "\n";

// Example 8: Conditional on nested objects with AND
echo "8. Conditional on nested objects with AND\n";
$data8 = new stdClass();
$data8->user = new stdClass();
$data8->user->type = 'business';
$data8->user->country = 'FR';
$data8->user->vat_number = '';

$dv8 = new DataVerify($data8);
$dv8
    ->field('user')->required->object
        ->subfield('vat_number')
        ->when('user.type', '=', 'business')
        ->and('user.country', 'in', ['FR', 'DE', 'IT'])
        ->then->required->string->minLength(9);

if (!$dv8->verify()) {
    echo "   ✗ VAT required for business users in EU\n";
} else {
    echo "   ✓ Validation passed\n";
}
echo "\n";

// Example 9: Deeply nested paths with conditions
echo "9. Deeply nested paths\n";
$data9 = new stdClass();
$data9->config = new stdClass();
$data9->config->features = new stdClass();
$data9->config->features->advanced = new stdClass();
$data9->config->features->advanced->enabled = true;
$data9->config->features->advanced->api_key = '';

$dv9 = new DataVerify($data9);
$dv9
    ->field('config')->required->object
        ->subfield('features')->required->object
            ->subfield('features', 'advanced')->required->object
                ->subfield('features', 'advanced', 'api_key')
                ->when('config.features.advanced.enabled', '=', true)
                ->then->required->string;

if (!$dv9->verify()) {
    echo "   ✗ API key required when advanced features enabled\n";
} else {
    echo "   ✓ Validation passed\n";
}
echo "\n";

// Example 10: Success case - condition not met
echo "10. Success when condition is not met\n";
$data10 = new stdClass();
$data10->delivery_type = 'pickup';
$data10->shipping_address = '';

$dv10 = new DataVerify($data10);
$dv10
    ->field('shipping_address')
    ->when('delivery_type', '=', 'shipping')
    ->then->required;

if ($dv10->verify()) {
    echo "   ✓ Shipping address not required for pickup\n";
} else {
    echo "   ✗ Validation failed\n";
}
echo "\n";

// Example 11: Using 'not_in' operator with OR
echo "11. Not-in operator with OR conditions\n";
$data11 = new stdClass();
$data11->payment_method = 'crypto';
$data11->kyc_document = '';

$dv11 = new DataVerify($data11);
$dv11
    ->field('kyc_document')
    ->when('payment_method', '=', 'crypto')
    ->or('payment_method', '=', 'wire_transfer')
    ->then->required->string;

if (!$dv11->verify()) {
    echo "   ✗ KYC document required for crypto or wire transfers\n";
} else {
    echo "   ✓ Validation passed\n";
}
echo "\n";

// Example 12: Multiple fields with different OR conditions
echo "12. Multiple fields with different OR conditions\n";
$data12 = new stdClass();
$data12->status = 'pending';
$data12->priority = 'high';
$data12->approval = '';
$data12->escalation = '';

$dv12 = new DataVerify($data12);
$dv12
    ->field('approval')
    ->when('status', '=', 'pending')
    ->or('status', '=', 'review')
    ->then->required
    
    ->field('escalation')
    ->when('priority', '=', 'high')
    ->or('priority', '=', 'urgent')
    ->then->required;

if (!$dv12->verify()) {
    echo "   ✗ Multiple conditional validations failed:\n";
    foreach ($dv12->getErrors() as $error) {
        echo "     - {$error['field']}\n";
    }
} else {
    echo "   ✓ Validation passed\n";
}
echo "\n";

echo "=== End of examples ===\n";