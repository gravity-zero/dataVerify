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

// Example 13: Deferred conditional evaluation - default + conditional rules
echo "13. Mix default and conditional rules (new in v1.1.0)\n";
$data13 = new stdClass();
$data13->user = new stdClass();
$data13->user->id = 123;
$data13->user->password = 'weak';

$dv13 = new DataVerify($data13);
$dv13
    ->field('user')->required->object
        ->subfield('password')
            ->minLength(8)                      // Always applied
            ->containsUpper                     // Always applied
            ->when('user.id', '!=', null)
                ->then->required;               // Applied if user.id exists

if (!$dv13->verify()) {
    echo "   ✗ Password must be 8+ chars with uppercase, and required for existing users\n";
} else {
    echo "   ✓ Validation passed\n";
}
echo "\n";

// Example 14: Multiple conditional blocks on same field
echo "14. Multiple conditional blocks on same field\n";
$data14 = new stdClass();
$data14->user = new stdClass();
$data14->user->id = 456;
$data14->user->role = 'admin';
$data14->user->password = 'ShortPass1!';

$dv14 = new DataVerify($data14);
$dv14
    ->field('user')->required->object
        ->subfield('password')
            ->minLength(8)                      // Default
            ->when('user.id', '!=', null)
                ->then->required                // Block 1: required for existing users
            ->when('user.role', '=', 'admin')
                ->then->minLength(16)           // Block 2: 16+ chars for admins
                    ->containsSpecialCharacter; // Block 2: special char for admins

if (!$dv14->verify()) {
    echo "   ✗ Admin password requires 16+ characters with special character\n";
    foreach ($dv14->getErrors() as $error) {
        echo "     - {$error['field']}: {$error['test']} failed\n";
    }
} else {
    echo "   ✓ Validation passed\n";
}
echo "\n";

// Example 15: Conditional blocks across multiple fields
echo "15. Conditional blocks across multiple fields\n";
$data15 = new stdClass();
$data15->subscription = new stdClass();
$data15->subscription->plan = 'enterprise';
$data15->subscription->users = 5;
$data15->subscription->payment_method = 'invoice';
$data15->subscription->billing_email = '';
$data15->subscription->card_number = '';

$dv15 = new DataVerify($data15);
$dv15
    ->field('subscription')->required->object
        ->subfield('billing_email')
            ->email
            ->when('subscription.plan', '=', 'enterprise')
                ->then->required
        ->subfield('card_number')
            ->when('subscription.payment_method', '=', 'card')
                ->then->required->regex('/^\d{16}$/')
        ->subfield('users')
            ->int
            ->when('subscription.plan', 'in', ['pro', 'enterprise'])
                ->then->required->between(1, 1000);

if (!$dv15->verify()) {
    echo "   ✗ Enterprise subscription validation failed:\n";
    foreach ($dv15->getErrors() as $error) {
        echo "     - {$error['field']}: {$error['message']}\n";
    }
} else {
    echo "   ✓ Validation passed\n";
}
echo "\n";

// Example 16: Conditional with complex AND/OR combinations
echo "16. Complex AND/OR combinations\n";
$data16 = new stdClass();
$data16->tier = 'premium';
$data16->status = 'active';
$data16->country = 'FR';
$data16->features = [];

$dv16 = new DataVerify($data16);
$dv16
    ->field('features')
        ->array
        ->when('tier', '=', 'premium')
        ->and('status', '=', 'active')
        ->and('country', 'in', ['FR', 'DE', 'BE'])
        ->then->required;

if (!$dv16->verify()) {
    echo "   ✗ Premium features required for active premium users in EU\n";
} else {
    echo "   ✓ Validation passed\n";
}
echo "\n";

// Example 17: API REST use case - POST vs PATCH
echo "17. API REST - POST vs PATCH pattern\n";

// POST /users - creating new user (id = null)
echo "   POST /users (new user):\n";
$postData = new stdClass();
$postData->user = new stdClass();
$postData->user->id = null;
$postData->user->email = 'new@example.com';
$postData->user->password = 'OptionalPass123';

$dvPost = new DataVerify($postData);
$dvPost
    ->field('user')->required->object
        ->subfield('email')
            ->email                             // Format checked if present
            ->when('user.id', '!=', null)
                ->then->required                // Not required for POST
        ->subfield('password')
            ->minLength(12)                     // Format checked if present
            ->when('user.id', '!=', null)
                ->then->required;               // Not required for POST

if ($dvPost->verify()) {
    echo "     ✓ Valid - email and password optional for new users\n";
} else {
    echo "     ✗ Validation failed\n";
}

// PATCH /users/123 - updating existing user (id exists)
echo "   PATCH /users/123 (update user):\n";
$patchData = new stdClass();
$patchData->user = new stdClass();
$patchData->user->id = 123;
$patchData->user->email = '';
$patchData->user->password = '';

$dvPatch = new DataVerify($patchData);
$dvPatch
    ->field('user')->required->object
        ->subfield('email')
            ->email
            ->when('user.id', '!=', null)
                ->then->required                // Required for PATCH
        ->subfield('password')
            ->minLength(12)
            ->when('user.id', '!=', null)
                ->then->required;               // Required for PATCH

if (!$dvPatch->verify()) {
    echo "     ✗ Validation failed - email and password required for updates\n";
} else {
    echo "     ✓ Validation passed\n";
}
echo "\n";

echo "=== End of conditional validation examples ===\n";