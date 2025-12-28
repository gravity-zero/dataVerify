# DataVerify

A fluent, zero-dependency PHP validation library.

[![Latest Version](https://img.shields.io/packagist/v/gravity/dataverify.svg?style=flat-square)](https://packagist.org/packages/gravity/dataverify)
[![Total Downloads](https://img.shields.io/packagist/dt/gravity/dataverify.svg?style=flat-square)](https://packagist.org/packages/gravity/dataverify)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENCE)

```bash
composer require gravity/dataverify
```

## Quick Start
```php
use Gravity\DataVerify;

$data = (object)[
    'email' => 'user@example.com',
    'age' => 25
];

$dv = new DataVerify($data);
$dv
    ->field('email')->required->email
    ->field('age')->required->int->between(18, 100);

if (!$dv->verify()) {
    print_r($dv->getErrors());
}
```

## Key Features

**Fluent validation**
```php
$dv->field('email')->required->email->maxLength(100);
```

**Nested objects**
```php
$dv->field('user')->required->object
    ->subfield('name')->required->string
    ->subfield('email')->required->email;
```

**Conditional validation**
```php
$dv->field('vat_number')
    ->when('country', '=', 'FR')
    ->then->required->regex('/^FR\d{11}$/');
```

**Custom strategies**
```php
class SiretStrategy extends ValidationStrategy {
    public function getName(): string { return 'siret'; }
    protected function handler(mixed $value): bool { /* validation logic */ }
}

DataVerify::global()->register(new SiretStrategy());
$dv->field('siret')->siret; // Available everywhere
```

## Use Cases

<details>
<summary><strong>REST APIs & Web Services</strong></summary>

Perfect for validating incoming API requests with conditional logic:
```php
// POST /api/users/register
$data = json_decode(file_get_contents('php://input'));

$dv = new DataVerify($data);
$dv
    ->field('email')->required->email->disposableEmail
    ->field('password')->required->minLength(8)
        ->containsUpper->containsNumber->containsSpecialCharacter
    ->field('company_name')
        ->when('account_type', '=', 'business')
        ->then->required->string->minLength(2)
    ->field('vat_number')
        ->when('account_type', '=', 'business')
        ->and('country', 'in', ['FR', 'BE', 'DE'])
        ->then->required->regex('/^[A-Z]{2}\d{9,12}$/');

if (!$dv->verify(batch: false)) { // Fail-fast for better performance
    http_response_code(400);
    echo json_encode(['errors' => $dv->getErrors()]);
    exit;
}
```

[See full example](examples/api-request.php)

</details>

<details>
<summary><strong>Form Validation (WordPress, PrestaShop, Laravel)</strong></summary>

Validate complex forms with dependent fields:
```php
// E-commerce checkout form
$dv = new DataVerify($_POST);
$dv
    ->field('shipping_method')->required->in(['standard', 'express', 'pickup'])
    ->field('shipping_address')
        ->when('shipping_method', '!=', 'pickup')
        ->then->required->string->minLength(10)
    ->field('shipping_city')
        ->when('shipping_method', '!=', 'pickup')
        ->then->required->string
    ->field('pickup_store')
        ->when('shipping_method', '=', 'pickup')
        ->then->required->string
    ->field('gift_message')
        ->when('is_gift', '=', true)
        ->then->required->maxLength(500);

if (!$dv->verify()) {
    // Display errors in form
    foreach ($dv->getErrors() as $error) {
        echo "<p class='error'>{$error['message']}</p>";
    }
}
```

[See full example](examples/basic.php)

</details>

<details>
<summary><strong>Data Processing Pipelines</strong></summary>

Validate batches of data with fail-fast for early termination:
```php
// Process CSV import with 10,000 rows
foreach ($csvRows as $row) {
    $dv = new DataVerify($row);
    $dv
        ->field('sku')->required->alphanumeric
        ->field('price')->required->numeric->greaterThan(0)
        ->field('stock')->int->between(0, 99999);
    
    if (!$dv->verify(batch: false)) { // Stop at first error per row
        $failedRows[] = ['row' => $row, 'errors' => $dv->getErrors()];
        continue; // Skip invalid row
    }
    
    // Process valid row...
}
```

</details>

<details>
<summary><strong>Conditional Business Rules</strong></summary>

Complex validation logic based on multiple conditions:
```php
// SaaS subscription validation
$dv = new DataVerify($subscription);
$dv
    ->field('plan')->required->in(['free', 'pro', 'enterprise'])
    ->field('payment_method')
        ->when('plan', '!=', 'free')
        ->then->required->in(['card', 'invoice'])
    ->field('card_number')
        ->when('plan', '!=', 'free')
        ->and('payment_method', '=', 'card')
        ->then->required->regex('/^\d{16}$/')
    ->field('billing_email')
        ->when('plan', '=', 'enterprise')
        ->or('payment_method', '=', 'invoice')
        ->then->required->email
    ->field('seats')
        ->when('plan', 'in', ['pro', 'enterprise'])
        ->then->required->int->between(1, 1000);
```

[See conditional examples](examples/conditional-validation.php)

</details>

<details>
<summary><strong>Multi-language Applications</strong></summary>

Built-in i18n with automatic locale detection:
```php
$dv = new DataVerify($data);
$dv->setLocale('fr'); // Built-in: EN, FR

$dv->field('email')->required->email;

if (!$dv->verify()) {
    // Error message in French:
    // "Le champ email doit être une adresse email valide"
    echo $dv->getErrors()[0]['message'];
}

// Add custom translations
$dv->addTranslations([
    'validation.siret' => 'El campo {field} debe ser un SIRET válido'
], 'es');
```

[See translation examples](examples/translation-basic.php)

</details>

## Why DataVerify?

- ✅ **Zero dependencies** - Pure PHP 8.1+, no vendor bloat
- ✅ **Fluent API** - Readable, chainable validations
- ✅ **Extensible** - Custom strategies with auto-documentation
- ✅ **Fast** - ~50μs simple validation, ~4.9MB memory ([benchmarks](docs/BENCHMARK.md))
- ✅ **i18n ready** - Built-in translation support (EN, FR)
- ✅ **Framework agnostic** - Works with WordPress, Laravel, Symfony, vanilla PHP
- ✅ **Production tested** - 346 tests, 72% mutation score

## Documentation

**Guides:**
- [Conditional Validation](docs/CONDITIONAL_VALIDATION.md) - `when/and/or/then` syntax
- [Custom Strategies](docs/CUSTOM_STRATEGIES.md) - Extend with your own rules
- [Internationalization](docs/INTERNATIONALIZATION.md) - Multi-language error messages
- [Error Handling](docs/ERROR_HANDLING.md) - Working with validation errors
- [Validation Rules](docs/VALIDATIONS.md) - All 40+ built-in rules

**Examples:**
- [API Request Validation](examples/api-request.php)
- [Basic Usage](examples/basic.php)
- [Conditional Validation](examples/conditional-validation.php)
- [Custom Validation Rules](examples/custom-validation.php)
- [Translation (PHP)](examples/translation-basic.php)
- [Translation (YAML)](examples/translation-yaml.php)

## Performance

DataVerify is designed for production with predictable sub-millisecond performance:
```
Simple validation:     ~50μs  (99% < 50μs)
Complex nested:        ~72μs  (99% < 72μs)
Batch mode (100 fields): 1.5ms
Fail-fast (100 fields): 0.7ms  (2x faster)
Memory usage:          ~4.9MB (stable)
```

**See:** [Full benchmarks](docs/BENCHMARK.md)

## Compatibility

| Platform | Status | Notes |
|----------|--------|-------|
| **PHP 8.1+** | ✅ Required | Minimum version |
| **WordPress 6.0+** | ✅ Compatible | Use in plugins/themes |
| **PrestaShop 8.0+** | ✅ Excellent fit | Native Composer support |
| **Laravel/Symfony** | ✅ Compatible | Use as alternative validator |
| **Moodle 4.3+** | ⚠️ Partial | Best for webservices/APIs |

## Examples

**Basic validation**
```php
$dv = new DataVerify($data);
$dv
    ->field('name')->required->string->minLength(3)
    ->field('email')->required->email
    ->field('age')->int->between(18, 120);

if (!$dv->verify()) {
    print_r($dv->getErrors());
}
```

**Batch vs fail-fast**
```php
$dv->verify();              // Batch mode: all errors
$dv->verify(batch: false);  // Fail-fast: first error only (2x faster)
```

**Optional fields**
```php
$dv->field('phone')->string;  // Optional: null OK, if present must be valid
$dv->field('email')->required->email;  // Required: must exist AND be valid
```

**Custom error messages**
```php
$dv->field('password')
    ->required
    ->minLength(8)->errorMessage('Password too weak - min 8 characters')
    ->containsUpper->errorMessage('Password must include uppercase letters');
```

**Nested validation**
```php
// Simple object nesting
$dv->field('user')->required->object
    ->subfield('profile')->required->object
        ->subfield('address')->required->object
            ->subfield('city')->required->string
            ->subfield('country')->required->string;

// Deep array nesting with indices
// Validates: data.orders[0].items[2].name
$dv->field('orders')->required->array
    ->subfield('0', 'items', '2', 'name')->required->string->minLength(3);

// Complex nested structures (arrays + objects)
// Validates: data.warehouses[0].inventory[1].product.sku
$dv->field('warehouses')->required->array
    ->subfield('0', 'inventory', '1', 'product', 'sku')->required->alphanumeric
    ->subfield('0', 'inventory', '1', 'product', 'stock')->required->int->between(0, 9999);
```

## FAQ

<details>
<summary><strong>Is sanitization included?</strong></summary>

**No.** DataVerify validates data but does NOT sanitize it. Always sanitize user input before validation:
```php
$data->email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$data->name = htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8');

$dv = new DataVerify($data);
$dv->field('email')->required->email;
```

</details>

<details>
<summary><strong>Can I reuse a DataVerify instance?</strong></summary>

**No.** Each validation requires a new instance to prevent state corruption:
```php
// ✅ Correct
foreach ($users as $user) {
    $dv = new DataVerify($user); // New instance
    $dv->field('email')->required->email;
    $dv->verify();
}

// ❌ Wrong - throws LogicException
$dv = new DataVerify($user1);
$dv->verify();
$dv->verify(); // Error: already verified
```

</details>

<details>
<summary><strong>How do I validate arrays of items?</strong></summary>

Loop and validate each item individually:
```php
foreach ($data->items as $item) {
    $dv = new DataVerify($item);
    $dv->field('sku')->required->alphanumeric
       ->field('qty')->required->int->between(1, 100);
    
    if (!$dv->verify()) {
        $errors[] = $dv->getErrors();
    }
}
```

</details>

## Contributing

Contributions welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

## License

MIT License - see [LICENSE](LICENCE) file for details.

---

**Made with ❤️ by [Romain Feregotto](https://github.com/gravity-zero)**
