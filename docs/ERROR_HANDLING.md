# Error Handling

DataVerify provides flexible error retrieval with detailed information about validation failures.

## Table of Contents

- [Quick Start](#quick-start)
- [Error Structure](#error-structure)
- [Getting Errors](#getting-errors)
- [Batch vs Fail-Fast](#batch-vs-fail-fast)
- [Custom Error Messages](#custom-error-messages)
- [Field Aliases](#field-aliases)
- [Error Formatting Examples](#error-formatting-examples)

---

## Quick Start

```php
use Gravity\DataVerify;

$data = (object)[
    'email' => 'invalid',
    'age' => 15
];

$dv = new DataVerify($data);
$dv
    ->field('email')->required->email
    ->field('age')->required->int->between(18, 100);

if (!$dv->verify()) {
    $errors = $dv->getErrors();
    
    foreach ($errors as $error) {
        echo $error['message'] . "\n";
        // The field email must be a valid email address
        // The field age must be between 18 and 100
    }
}
```

---

## Error Structure

Each error contains detailed information about the validation failure:

<details>
<summary><strong>Error Array Structure</strong></summary>

```php
[
    'field' => 'email',           // Field name (or nested path like 'user.email')
    'alias' => 'Email Address',   // Custom display name (if set)
    'test' => 'email',            // Validation rule that failed
    'message' => 'The field Email Address must be a valid email address',
    'value' => 'invalid'          // The value that failed validation
]
```

**Fields:**
- `field` - The field name or nested path (`user.email`, `address.city`)
- `alias` - Custom display name set with `->alias()`, or `null`
- `test` - Name of the validation that failed (`required`, `email`, `between`, etc.)
- `message` - Translated error message (respects current locale)
- `value` - The actual value that failed validation

</details>

<details>
<summary><strong>Nested Field Paths</strong></summary>

```php
$data = (object)[
    'user' => (object)[
        'profile' => (object)[
            'email' => 'invalid'
        ]
    ]
];

$dv = new DataVerify($data);
$dv
    ->field('user')->required->object
        ->subfield('profile')->required->object
            ->subfield('profile', 'email')->required->email;

if (!$dv->verify()) {
    $errors = $dv->getErrors();
    echo $errors[0]['field'];  // "user.profile.email"
}
```

**Note:** Nested paths use dot notation (`parent.child.grandchild`)

</details>

---

## Getting Errors

<details>
<summary><strong>As Arrays (Default)</strong></summary>

```php
$errors = $dv->getErrors();

foreach ($errors as $error) {
    echo "{$error['field']}: {$error['message']}\n";
}
```

**Returns:** Array of associative arrays

</details>

<details>
<summary><strong>As Objects</strong></summary>

```php
$errors = $dv->getErrors(asObject: true);

foreach ($errors as $error) {
    echo $error->field . ": " . $error->message . "\n";
    
    // Access properties
    $fieldName = $error->field;
    $displayName = $error->alias;
    $ruleName = $error->test;
    $message = $error->message;
    $failedValue = $error->value;
}
```

**Returns:** Array of `ValidationError` objects

</details>

<details>
<summary><strong>Checking for Specific Fields</strong></summary>

```php
$errors = $dv->getErrors();

// Find errors for specific field
$emailErrors = array_filter($errors, fn($e) => $e['field'] === 'email');

// Check if field has errors
$hasEmailError = !empty($emailErrors);
```

</details>

---

## Batch vs Fail-Fast

<details>
<summary><strong>Batch Mode (Default)</strong></summary>

Collects **all** validation errors before returning:

```php
$dv = new DataVerify($data);
$dv
    ->field('email')->required->email
    ->field('age')->required->int->between(18, 100)
    ->field('name')->required->string->minLength(3);

$dv->verify();  // or verify(batch: true)

$errors = $dv->getErrors();
// Returns ALL errors from email, age, and name
```

**Use when:** You want to show users all validation issues at once

</details>

<details>
<summary><strong>Fail-Fast Mode</strong></summary>

Stops at the **first** validation error:

```php
$dv->verify(batch: false);

$errors = $dv->getErrors();
// Returns ONLY the first error encountered
```

**Performance:** ~2x faster than batch mode (stops early)

**Use when:** 
- You want progressive feedback
- Performance is critical
- Early exit on first failure is acceptable

</details>

---

## Custom Error Messages

<details>
<summary><strong>Per-Field Custom Messages</strong></summary>

```php
$dv->field('email')
    ->required
    ->email
    ->errorMessage('Please provide a valid professional email address');

// Error message: "Please provide a valid professional email address"
// (ignores default translations)
```

**Note:** Custom message applies to **all validations** on that field

</details>

<details>
<summary><strong>When to Use</strong></summary>

**Use custom messages for:**
- Business-specific requirements
- User-friendly explanations
- Context-specific guidance

```php
$dv->field('corporate_email')
    ->required
    ->email
    ->regex('/@company\.com$/')
    ->errorMessage('Please use your @company.com email address');
```

**Use translations for:**
- Standard validation messages
- Multi-language support
- Consistency across application

</details>

---

## Field Aliases

<details>
<summary><strong>Setting Display Names</strong></summary>

```php
// Without alias
$dv->field('user_email')->required;
// Error: "The field user_email is required"

// With alias
$dv->field('user_email')->alias('Email Address')->required;
// Error: "The field Email Address is required"
```

**Benefit:** Human-friendly field names in error messages

</details>

<details>
<summary><strong>Nested Field Aliases</strong></summary>

```php
$dv
    ->field('user')->required->object
        ->subfield('first_name')->alias('First Name')->required->string
        ->subfield('last_name')->alias('Last Name')->required->string;

// Errors:
// "The field First Name is required"
// "The field Last Name is required"
```

</details>

---

## Error Formatting Examples

<details>
<summary><strong>JSON API Response</strong></summary>

```php
if (!$dv->verify()) {
    return response()->json([
        'success' => false,
        'errors' => $dv->getErrors()
    ], 422);
}

// Response:
// {
//   "success": false,
//   "errors": [
//     {
//       "field": "email",
//       "alias": "Email Address",
//       "test": "email",
//       "message": "The field Email Address must be a valid email address",
//       "value": "invalid"
//     }
//   ]
// }
```

</details>

<details>
<summary><strong>Grouped by Field</strong></summary>

```php
$errors = $dv->getErrors();

$grouped = [];
foreach ($errors as $error) {
    $field = $error['field'];
    if (!isset($grouped[$field])) {
        $grouped[$field] = [];
    }
    $grouped[$field][] = $error['message'];
}

// Result:
// [
//   'email' => ['The field email must be a valid email address'],
//   'age' => ['The field age must be between 18 and 100']
// ]
```

</details>

<details>
<summary><strong>HTML Error List</strong></summary>

```php
$errors = $dv->getErrors();

echo '<ul class="errors">';
foreach ($errors as $error) {
    $field = htmlspecialchars($error['field']);
    $message = htmlspecialchars($error['message']);
    echo "<li data-field=\"{$field}\">{$message}</li>";
}
echo '</ul>';
```

</details>

<details>
<summary><strong>Console Output</strong></summary>

```php
if (!$dv->verify()) {
    $errors = $dv->getErrors();
    
    echo "Validation failed:\n";
    foreach ($errors as $error) {
        echo "  ✗ {$error['field']}: {$error['message']}\n";
    }
    
    exit(1);
}
```

</details>

<details>
<summary><strong>First Error Only</strong></summary>

```php
if (!$dv->verify()) {
    $errors = $dv->getErrors();
    $firstError = $errors[0] ?? null;
    
    if ($firstError) {
        echo "Error: " . $firstError['message'];
    }
}
```

</details>

---

## See Also

- **[Internationalization](INTERNATIONALIZATION.md)** - Multi-language error messages
- **[Validation Rules](VALIDATIONS.md)** - All available validation rules
- **[Custom Strategies](CUSTOM_STRATEGIES.md)** - Error messages for custom validations
