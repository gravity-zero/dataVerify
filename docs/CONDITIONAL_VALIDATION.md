# Conditional Validation

DataVerify supports conditional validation where validation rules are only applied when specific conditions are met. Combine conditions using `and()` and `or()` operators.

## Table of Contents

- [Quick Start](#quick-start)
- [Basic Concepts](#basic-concepts)
- [Supported Operators](#supported-operators)
- [AND / OR Conditions](#and--or-conditions)
- [Nested Fields](#nested-fields)
- [Restrictions](#restrictions)
- [Common Use Cases](#common-use-cases)
- [Best Practices](#best-practices)

---

## Quick Start
```php
$data->delivery_type = "shipping";
$data->shipping_address = "";

$dv = new DataVerify($data);
$dv->field('shipping_address')
   ->when('delivery_type', '=', 'shipping')
   ->then->required->string;

// shipping_address only required when delivery_type equals 'shipping'
```

**Pattern:**
```php
$dv->field('target')
   ->when('condition_field', 'operator', 'value')
   ->then->validation_rules;
```

---

## Basic Concepts

<details>
<summary><strong>when() and then</strong></summary>

**when()** - Define condition(s) that must be met  
**then** - Activate conditional mode (always required after when/and/or)
```php
$dv->field('email')
   ->when('status', '=', 'active')
   ->then->required->email;

// email validated only when status equals 'active'
```

**With nested fields (dot notation):**
```php
$data->user = new stdClass();
$data->user->role = 'admin';

$dv->field('admin_key')
   ->when('user.role', '=', 'admin')
   ->then->required->string;
```

</details>

---

## Supported Operators

| Operator | Description | Example |
|----------|-------------|---------|
| `=` | Equal (strict) | `->when('status', '=', 'active')` |
| `!=` | Not equal (strict) | `->when('type', '!=', 'guest')` |
| `<` | Less than | `->when('age', '<', 18)` |
| `>` | Greater than | `->when('amount', '>', 100)` |
| `<=` | Less than or equal | `->when('age', '<=', 65)` |
| `>=` | Greater than or equal | `->when('age', '>=', 18)` |
| `in` | In array (strict) | `->when('country', 'in', ['FR', 'DE'])` |
| `not_in` | Not in array (strict) | `->when('role', 'not_in', ['admin'])` |

**All comparisons use strict mode** (`===`, `!==`, `in_array(..., true)`)

---

## AND / OR Conditions

<details>
<summary><strong>AND - All Must Be True</strong></summary>
```php
$dv->field('discount_code')
   ->when('type', '=', 'premium')
   ->and('amount', '>', 100)
   ->then->required->string;

// Required when type='premium' AND amount>100
```

**Multiple AND:**
```php
$dv->field('premium_feature')
   ->when('age', '>=', 18)
   ->and('country', 'in', ['FR', 'BE'])
   ->and('income', '>', 30000)
   ->then->required;

// All three conditions must be true
```

</details>

<details>
<summary><strong>OR - At Least One Must Be True</strong></summary>
```php
$dv->field('vat_number')
   ->when('country', '=', 'FR')
   ->or('country', '=', 'BE')
   ->or('country', '=', 'DE')
   ->then->required->string;

// Required if country is FR OR BE OR DE
```

**Tip:** Use `in` operator instead of multiple `or()`:
```php
// ✅ Cleaner
$dv->field('vat_number')
   ->when('country', 'in', ['FR', 'BE', 'DE'])
   ->then->required;
```

</details>

---

## Nested Fields

<details>
<summary><strong>Dot Notation for Nested Objects</strong></summary>
```php
$data->user = new stdClass();
$data->user->type = "business";
$data->user->country = "FR";

$dv = new DataVerify($data);
$dv->field('user')->required->object
   ->subfield('vat_number')
   ->when('user.type', '=', 'business')
   ->and('user.country', 'in', ['FR', 'DE', 'IT'])
   ->then->required->string;

// vat_number required when user.type='business' AND country is EU
```

**Use full path from root**, not relative paths.

</details>

---

## Restrictions

<details>
<summary><strong>Cannot Mix AND/OR</strong></summary>

**You cannot mix `and()` and `or()` in the same chain:**
```php
// ❌ Invalid
->when('x', '=', 1)
->and('y', '>', 0)
->or('z', '<', 100)  // Error!
->then->required

// ✅ Valid - All AND
->when('x', '=', 1)
->and('y', '>', 0)
->and('z', '<', 100)
->then->required

// ✅ Valid - All OR
->when('a', '=', 1)
->or('b', '=', 2)
->or('c', '=', 3)
->then->required
```

**Workaround for complex logic `(A AND B) OR C`:**
```php
// Split into two separate validations
$dv->field('discount')
   ->when('type', '=', 'premium')
   ->and('amount', '>', 100)
   ->then->required;

$dv->field('discount')
   ->when('special_offer', '=', true)
   ->then->required;

// If EITHER condition is true, discount is required
```

</details>

<details>
<summary><strong>Must Use 'then' After Conditions</strong></summary>
```php
// ❌ Missing 'then'
->when('status', '=', 'active')
->required  // Error!

// ✅ Correct
->when('status', '=', 'active')
->then->required
```

</details>

---

## Common Use Cases

<details>
<summary><strong>Age-Based Validation</strong></summary>
```php
$dv->field('parental_consent')
   ->when('age', '<', 18)
   ->then->required->boolean;

// Consent required for users under 18
```

</details>

<details>
<summary><strong>Business Account</strong></summary>
```php
$dv->field('company_name')
   ->when('account_type', '=', 'business')
   ->then->required->string
   
   ->field('tax_id')
   ->when('account_type', '=', 'business')
   ->and('country', 'in', ['FR', 'DE', 'IT'])
   ->then->required->string;

// company_name required for all business accounts
// tax_id required for business accounts in EU
```

</details>

<details>
<summary><strong>Shipping Logic</strong></summary>
```php
$dv->field('shipping_address')
   ->when('delivery_type', '=', 'shipping')
   ->then->required->string;

// Address only required when shipping (not pickup)
```

</details>

<details>
<summary><strong>Payment Validation</strong></summary>
```php
$dv->field('kyc_document')
   ->when('payment_method', 'in', ['crypto', 'wire_transfer'])
   ->then->required->string
   
   ->field('wallet_address')
   ->when('payment_method', '=', 'crypto')
   ->then->required->regex('/^0x[a-fA-F0-9]{40}$/');

// KYC for high-risk payments, wallet for crypto
```

</details>

<details>
<summary><strong>Mix Normal and Conditional</strong></summary>
```php
$dv->field('email')
   ->required      // ← Always required
   ->email         // ← Always validated
   
   ->field('phone')
   ->when('contact_preference', '=', 'phone')
   ->then->required  // ← Only when condition true
   ->regex('/^\+?[1-9]\d{1,14}$/');  // ← Always validated if present
```

**Note:** Validations **after** `then` only run if condition is true. Validations **before** `when()` always run.

</details>

---

## Best Practices

**✅ Keep conditions simple:**
```php
// Good - 1-2 conditions
->when('type', '=', 'premium')
->and('amount', '>', 100)
->then->required

// Too complex - split it
->when('account_type', '=', 'business')
->and('country', 'in', ['FR', 'DE', 'IT'])
->and('annual_revenue', '>', 100000)
->and('employees', '>', 50)
->then->required
```

**✅ Use `in` operator over multiple `or()`:**
```php
// Bad - verbose
->when('country', '=', 'FR')
->or('country', '=', 'DE')
->or('country', '=', 'IT')
->then->required

// Good - clean
->when('country', 'in', ['FR', 'DE', 'IT'])
->then->required
```

**✅ Document complex business rules:**
```php
// EU VAT required for businesses >100k revenue
$dv->field('vat_number')
   ->when('account_type', '=', 'business')
   ->and('country', 'in', ['FR', 'DE', 'IT'])
   ->and('annual_revenue', '>', 100000)
   ->then->required->string;
```

**❌ Don't reference the field you're validating:**
```php
// Wrong - circular reference
->field('email')
->when('email', '=', 'admin@example.com')
->then->required

// Correct - reference other fields
->field('admin_access')
->when('email', '=', 'admin@example.com')
->then->required
```

---

## See Also

- **[Validation Rules](VALIDATIONS.md)** - All available validation rules
- **[Error Handling](ERROR_HANDLING.md)** - Handle conditional validation errors
- **[Custom Strategies](CUSTOM_STRATEGIES.md)** - Custom conditional logic
- **[Internationalization](INTERNATIONALIZATION.md)** - Translate error messages