# Custom Validation Strategies

Extend DataVerify with your own validation logic through custom strategies.

## Table of Contents

- [Quick Start](#quick-start)
- [IDE Autocompletion](#ide-autocompletion)
- [Global vs Instance Registration](#global-vs-instance-registration)
- [Creating Strategies](#creating-strategies)
- [Translation and Error Messages](#translation-and-error-messages)
- [Registration Methods](#registration-methods)
- [Best Practices](#best-practices)
- [API Reference](#api-reference)

---

## Quick Start
```php
// 1. Create strategy
// The #[ValidationRule] attribute enables auto-documentation generation and IDE stub creation (See IDE Autocompletion)
#[ValidationRule(
    name: 'siret',
    description: 'Validates French SIRET number (14 digits with Luhn check)',
    category: 'Business',
    examples: ['$verifier->field("company")->siret']
)]
class SiretStrategy extends ValidationStrategy
{
    public function getName(): string { return 'siret'; }
    
    protected function handler(mixed $value): bool
    {
        return is_string($value) 
            && preg_match('/^\d{14}$/', $value)
            && $this->luhnCheck($value);
    }
}

// 2. Register globally
DataVerify::global()->loadFromDirectory(
    path: __DIR__ . '/app/Strategies',
    namespace: 'App\\Strategies'
);

// 3. Use anywhere
$dv->field('siret')->required->siret;
```

---

## IDE Autocompletion

<details>
<summary><strong>Enable Automatic IDE Helper</strong></summary>

```php
// bootstrap.php (development only)
if (getenv('APP_ENV') === 'development') {
    DataVerify::enableIdeHelper();
}

DataVerify::global()->register(new SiretStrategy());
// ✨ IDE now autocompletes ->siret()
```

Add to `.gitignore`:
```gitignore
/.ide-helper.php
```

**How it works:** Generates a stub file providing type hints for IDEs (PHPStorm, VSCode, etc.). Updates automatically when registering strategies.

</details>

<details>
<summary><strong>Manual Generation</strong></summary>

```php
use Gravity\Documentation\GeneratePhpDoc;

GeneratePhpDoc::writeIdeHelper(
    __DIR__ . '/.ide-helper.php',
    [new SiretStrategy(), new VatStrategy()]
);
```

</details>

---

## Global vs Instance Registration

<details>
<summary><strong>Global Strategies (Recommended)</strong></summary>

```php
// Register once - available everywhere
DataVerify::global()->loadFromDirectory(
    path: __DIR__ . '/app/Strategies',
    namespace: 'App\\Strategies'
);

$dv = new DataVerify($data);
$dv->field('siret')->siret;  // Works automatically
```

**Use for:** Business rules (SIRET, VAT), domain validations, shared across app.

</details>

<details>
<summary><strong>Instance Strategies</strong></summary>

```php
// Register per validation - not shared
$dv = new DataVerify($data);
$dv->registerStrategy(new TempStrategy());
$dv->field('special')->temp_validation;
```

**Use for:** One-off validations, testing, controller-specific logic.

</details>

---

## Creating Strategies

<details>
<summary><strong>Simple Strategy (No Parameters)</strong></summary>

```php
#[ValidationRule(
    name: 'positive',
    description: 'Validates that a numeric value is strictly positive (greater than zero)',
    category: 'Numeric',
    examples: ['$verifier->field("amount")->positive']
)]
class PositiveStrategy extends ValidationStrategy
{
    public function getName(): string { return 'positive'; }
    
    protected function handler(mixed $value): bool
    {
        return is_numeric($value) && $value > 0;
    }
}

// Usage
$dv->field('amount')->positive;
```

</details>

<details>
<summary><strong>Strategy with Parameters</strong></summary>

Parameter names automatically become translation placeholders:

```php
class DivisibleByStrategy extends ValidationStrategy
{
    public function getName(): string { return 'divisible_by'; }
    
    protected function handler(mixed $value, int $divisor): bool
    {
        return is_numeric($value) && $value % $divisor === 0;
    }
}

// Translation: "The {field} must be divisible by {divisor}"
//                                                  ^^^^^^^^ from $divisor parameter
```

</details>

<details>
<summary><strong>Strategy with Documentation</strong></summary>

```php
use Gravity\Attributes\{ValidationRule, Param};

#[ValidationRule(
    name: 'strong_password',
    description: 'Strong password (12+ chars, mixed case, digit, special)',
    category: 'Security'
)]
class StrongPasswordStrategy extends ValidationStrategy
{
    public function getName(): string { return 'strong_password'; }
    
    protected function handler(
        mixed $value,
        #[Param('Minimum length')]
        int $minLength = 12
    ): bool {
        return is_string($value)
            && strlen($value) >= $minLength
            && preg_match('/[A-Z]/', $value)
            && preg_match('/[a-z]/', $value)
            && preg_match('/[0-9]/', $value)
            && preg_match('/[^A-Za-z0-9]/', $value);
    }
}
```

**Note:** Attributes enable auto-generated documentation.

</details>

---

## Translation and Error Messages

<details>
<summary><strong>Named Parameters for Translations</strong></summary>

Parameter names in `handler()` automatically become placeholders:

```php
class PriceRangeStrategy extends ValidationStrategy
{
    public function getName(): string { return 'price_range'; }
    
    // Named parameters → {min} and {max} placeholders
    protected function handler(mixed $value, float $min, float $max): bool
    {
        return is_numeric($value) && $value >= $min && $value <= $max;
    }
}

// Add translations
$dv->addTranslations([
    'validation.price_range' => 'The {field} must be between {min}€ and {max}€'
], 'en');

$dv->field('price')->price_range(10.0, 99.99);
// Error: "The field price must be between 10€ and 99.99€"
```

</details>

<details>
<summary><strong>Standard Parameter Names</strong></summary>

Use conventional names to match built-in validations:

```php
protected function handler(
    mixed $value,
    int $min,           // → {min}
    int $max,           // → {max}
    string $format,     // → {format}
    array $allowed,     // → {allowed}
    string $pattern     // → {pattern}
): bool { }
```

</details>

---

## Registration Methods

<details>
<summary><strong>Auto-load from Directory</strong></summary>

```php
DataVerify::global()->loadFromDirectory(
    path: __DIR__ . '/app/Strategies',
    namespace: 'App\\Strategies'
);
```

**Recommended structure:**
```
app/Strategies/
├── SiretStrategy.php
├── IbanStrategy.php
└── VatStrategy.php
```

</details>

<details>
<summary><strong>Register Multiple</strong></summary>

```php
DataVerify::global()->registerMultiple([
    new SiretStrategy(),
    new IbanStrategy(),
]);
```

</details>

<details>
<summary><strong>Register Single</strong></summary>

```php
DataVerify::global()->register(new CustomStrategy());
```

</details>

<details>
<summary><strong>Fluent API</strong></summary>

```php
DataVerify::global()
    ->register(new Strategy1())
    ->registerMultiple([new Strategy2()])
    ->loadFromDirectory(__DIR__ . '/custom', 'App\\Custom');
```

</details>

---

## Best Practices

<details>
<summary><strong>Always Use Named Parameters</strong></summary>

```php
// ✅ Good - translation support
protected function handler(mixed $value, int $min, int $max): bool

// ❌ Bad - no translation placeholders
protected function handler(mixed $value, int $a, int $b): bool
```

**Important:** Never override `execute()` - put logic in `handler()`.

</details>

<details>
<summary><strong>When to Use Each Approach</strong></summary>

**Global:** Business rules (SIRET, VAT), domain validations, shared across app

**Instance:** One-off validations, testing, controller-specific logic

</details>

<details>
<summary><strong>Organize Strategies</strong></summary>

```
app/Strategies/
├── Business/
│   ├── SiretStrategy.php
│   └── VatStrategy.php
└── Security/
    └── StrongPasswordStrategy.php
```

</details>

<details>
<summary><strong>Validate Types Early</strong></summary>

```php
protected function handler(mixed $value, int $threshold): bool
{
    if (!is_numeric($value)) return false;  // Type check first
    if (empty($value)) return false;
    
    return $value >= $threshold;
}
```

</details>

---

## API Reference

<details>
<summary><strong>ValidationStrategy Base Class</strong></summary>

```php
abstract class ValidationStrategy
{
    abstract public function getName(): string;
    abstract protected function handler(mixed $value, mixed ...$args): bool;
    final public function execute(mixed $value, array $args): bool;
}
```

**Example:**
```php

#[ValidationRule(
    name: 'my_validation',
    description: 'Validates a value against custom business logic',
    category: 'My Domain'
)]
class MyStrategy extends ValidationStrategy
{
    public function getName(): string { return 'my_validation'; }
    
    protected function handler( mixed $value,
     #[Param(name: 'param', description: 'Validation threshold parameter')]
     int $param): bool
    {
        // $param becomes {param} in translations
    }
}
```

</details>

<details>
<summary><strong>GlobalStrategyRegistry Methods</strong></summary>

```php
DataVerify::global()->register(ValidationStrategyInterface $strategy): self // Register a single strategy
DataVerify::global()->registerMultiple(array $strategies): self // Register multiple strategies at once
DataVerify::global()->loadFromDirectory(string $path, string $namespace): self // Auto-discover and load strategies from a directory
DataVerify::global()->has(string $name): bool // Check if a strategy exists
DataVerify::global()->get(string $name): ?ValidationStrategyInterface // Retrieve a specific strategy
DataVerify::global()->clear(): self // Remove all registered strategies
DataVerify::global()->getAll(): array // Get all registered strategies
```

</details>

<details>
<summary><strong>DataVerify Instance Methods</strong></summary>

```php
$dv->registerStrategy(ValidationStrategyInterface $strategy): self
```

</details>

## See Also

- **[Validation Rules](VALIDATIONS.md)** - All built-in validation rules
- **[Internationalization](INTERNATIONALIZATION.md)** - Add translations for custom strategies
- **[Error Handling](ERROR_HANDLING.md)** - Custom error messages for strategies
- **[Conditional Validation](CONDITIONAL_VALIDATION.md)** - Use strategies with conditions