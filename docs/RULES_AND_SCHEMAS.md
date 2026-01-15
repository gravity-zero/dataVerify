# Reusable Rules and Schemas

**[← Back to Main Documentation](../README.md)**

**Navigation:** [Validations](VALIDATIONS.md) | [Conditional Validation](CONDITIONAL_VALIDATION.md) | **Rules & Schemas** | [Custom Strategies](CUSTOM_STRATEGIES.md) | [Error Handling](ERROR_HANDLING.md) | [Internationalization](INTERNATIONALIZATION.md) | [Benchmarks](BENCHMARK.md)

---

DataVerify supports two levels of reusability:
- **Rules**: Field-agnostic validation sets that can be applied to any field
- **Schemas**: Complete validation structures with field declarations

## Table of Contents

- [Rules - Reusable Validations](#rules---reusable-validations)
- [Schemas - Complete Structures](#schemas---complete-structures)
- [Loading from Files](#loading-from-files)
- [Rules vs Schemas](#rules-vs-schemas)
- [Best Practices](#best-practices)
- [Common Use Cases](#common-use-cases)

---

## Rules - Reusable Validations

Rules are **field-agnostic** validation sets. Perfect for common validation patterns that apply to multiple fields.

### Registering Rules

```php
use Gravity\DataVerify;

// Register a reusable rule
DataVerify::registerRules("strongPassword")
    ->minLength(12)
    ->containsUpper
    ->containsLower
    ->containsSpecialCharacter;
```

**Key characteristics:**
- ✅ No field declarations
- ✅ Can be applied to any field
- ✅ Chainable (multiple rules on same field)
- ❌ No conditional logic (when/then)

### Applying Rules

**Three syntaxes supported:**

```php
$dv = new DataVerify($data);

// Syntax 1: Method call
$dv->field('password')->rule('strongPassword');

// Syntax 2: Property chain
$dv->field('password')->rule->strongPassword;

// Syntax 3: Property with method call
$dv->field('password')->rule->strongPassword();
```

### Chaining Rules

```php
// Register multiple rules
DataVerify::registerRules('strong')->minLength(12)->containsUpper;
DataVerify::registerRules('secure')->containsSpecialCharacter;
DataVerify::registerRules('unique')->regex('/^[a-z0-9]+$/');

// Apply multiple rules
$dv->field('password')
    ->required
    ->rule('strong')
    ->rule('secure')
    ->rule('unique');
```

### Complete Example

```php
// Registration (bootstrap/config)
DataVerify::registerRules("email")
    ->email
    ->disposableEmail;

DataVerify::registerRules("strongPassword")
    ->minLength(12)
    ->containsUpper
    ->containsLower
    ->containsNumber
    ->containsSpecialCharacter;

DataVerify::registerRules("username")
    ->minLength(3)
    ->maxLength(20)
    ->alphanumeric;

// Usage (validation)
$data = [
    'email' => 'user@example.com',
    'password' => 'WeakPass123',
    'username' => 'john_doe'
];

$dv = new DataVerify($data);
$dv->field('email')->required->rule('email')
   ->field('password')->required->rule('strongPassword')
   ->field('username')->required->rule('username');

$dv->verify();
```

---

## Schemas - Complete Structures

Schemas are **complete validation structures** with field declarations. Perfect for API endpoints or complex objects.

### Registering Schemas

```php
DataVerify::registerSchema("userCreateOrPatch")
    ->field("user")->required->object
        ->subfield("email")
            ->email
            ->disposableEmail
            ->when("user.id", "!=", null)
                ->then->required
        ->subfield("password")
            ->minLength(12)
            ->containsUpper
            ->containsLower
            ->containsSpecialCharacter
            ->when("user.id", "!=", null)
                ->then->required;
```

**Key characteristics:**
- ✅ Declares fields and subfields
- ✅ Supports conditional logic (when/then)
- ✅ Can use registered Rules
- ❌ Only ONE schema per DataVerify instance

### Applying Schemas

**Three syntaxes supported:**

```php
$dv = new DataVerify($data);

// Syntax 1: Method call
$dv->schema('userCreateOrPatch');

// Syntax 2: Property chain
$dv->schema->userCreateOrPatch;

// Syntax 3: Property with method call
$dv->schema->userCreateOrPatch();
```

### Using Rules in Schemas

```php
// Register rules
DataVerify::registerRules('strongPassword')
    ->minLength(12)
    ->containsUpper
    ->containsLower;

// Use rules in schema
DataVerify::registerSchema('userApi')
    ->field('user')->required->object
        ->subfield('password')->rule('strongPassword')
        ->subfield('email')->email;

// Apply schema
$dv = new DataVerify($data);
$dv->schema('userApi');
```

### Complete Example - API POST/PATCH

```php
// Registration
DataVerify::registerRules('strongPassword')
    ->minLength(12)
    ->containsUpper
    ->containsLower
    ->containsSpecialCharacter;

DataVerify::registerSchema('userApi')
    ->field('user')->required->object
        ->subfield('email')
            ->email
            ->disposableEmail
            ->when('user.id', '=', null)
                ->then->required
        ->subfield('password')
            ->rule('strongPassword')
            ->when('user.id', '=', null)
                ->then->required
        ->subfield('name')
            ->string
            ->minLength(2)
            ->when('user.id', '=', null)
                ->then->required;

// POST /users (user.id = null)
$postData = [
    'user' => [
        'id' => null,
        'email' => 'new@example.com',
        'password' => 'StrongP@ss123',
        'name' => 'John'
    ]
];

$dv = new DataVerify($postData);
$dv->schema('userApi');
$dv->verify(); // email, password, name are required, and must be valid

// PATCH /users/:id (user.id exists)
$patchData = [
    'user' => [
        'id' => 123,
        'email' => '',
        'password' => 'weak'
    ]
];

$dv = new DataVerify($patchData);
$dv->schema('userApi');
$dv->verify(); // Fails: email, password, name are not required, but validated if provided
```

---

## Loading from Files

For better organization in larger projects, rules and schemas can be loaded from files.

### Loading Rules from Files

Each rule file should return a callable that registers the rule:

```php
// config/rules/strongPassword.php
<?php
return fn() => \Gravity\DataVerify::registerRules('strongPassword')
    ->minLength(12)
    ->containsUpper
    ->containsLower
    ->containsSpecialCharacter;
```

```php
// config/rules/emailFormat.php
<?php
return fn() => \Gravity\DataVerify::registerRules('emailFormat')
    ->email
    ->disposableEmail;
```

**Load all rules from a directory:**

```php
// In bootstrap file
DataVerify::loadRulesFrom(__DIR__ . '/config/rules');
```

**Directory structure:**
```
config/
└── rules/
    ├── strongPassword.php
    ├── emailFormat.php
    └── username.php
```

### Loading Schemas from Classes

Schemas are defined as classes implementing `SchemaConfigInterface`:

```php
// App/Validation/Schemas/UserSchema.php
<?php
namespace App\Validation\Schemas;

use Gravity\Interfaces\SchemaConfigInterface;
use Gravity\Schema\SchemaBuilder;

class UserSchema implements SchemaConfigInterface
{
    public function getName(): string
    {
        return 'user';
    }

    public function define(SchemaBuilder $builder): void
    {
        $builder
            ->field('email')->required->email
            ->field('name')->required->string->minLength(2)
            ->field('password')
                ->minLength(12)
                ->containsUpper
                ->when('id', '!=', null)
                    ->then->required;
    }
}
```

```php
// App/Validation/Schemas/ProductSchema.php
<?php
namespace App\Validation\Schemas;

use Gravity\Interfaces\SchemaConfigInterface;
use Gravity\Schema\SchemaBuilder;

class ProductSchema implements SchemaConfigInterface
{
    public function getName(): string
    {
        return 'product';
    }

    public function define(SchemaBuilder $builder): void
    {
        $builder
            ->field('title')->required->string->minLength(3)
            ->field('price')->required->numeric
            ->field('stock')->int;
    }
}
```

**Load all schemas from a directory:**

```php
// In bootstrap file
DataVerify::loadSchemasFrom(
    __DIR__ . '/App/Validation/Schemas',
    'App\\Validation\\Schemas'
);
```

**Directory structure:**
```
App/
└── Validation/
    └── Schemas/
        ├── UserSchema.php
        ├── ProductSchema.php
        └── OrderSchema.php
```

### Framework Integration Examples

**Symfony - Service Provider:**

```php
// src/Validator/ValidatorBootstrap.php
namespace App\Validator;

use Gravity\DataVerify;

class ValidatorBootstrap
{
    public function __construct()
    {
        // Load rules
        DataVerify::loadRulesFrom(__DIR__ . '/Rules');
        
        // Load schemas
        DataVerify::loadSchemasFrom(
            __DIR__ . '/Schemas',
            'App\\Validator\\Schemas'
        );
    }
}
```

**Laravel - AppServiceProvider:**

```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    DataVerify::loadRulesFrom(app_path('Validation/Rules'));
    DataVerify::loadSchemasFrom(
        app_path('Validation/Schemas'),
        'App\\Validation\\Schemas'
    );
}
```

**WordPress - Plugin Init:**

```php
add_action('init', function() {
    DataVerify::loadRulesFrom(__DIR__ . '/validation/rules');
    DataVerify::loadSchemasFrom(
        __DIR__ . '/validation/schemas',
        'MyPlugin\\Validation\\Schemas'
    );
});
```

**Vanilla PHP - Bootstrap:**

```php
// bootstrap.php
require 'vendor/autoload.php';

use Gravity\DataVerify;

DataVerify::loadRulesFrom(__DIR__ . '/config/rules');
DataVerify::loadSchemasFrom(
    __DIR__ . '/src/Schemas',
    'App\\Schemas'
);
```

### Using Custom Strategies in Rules and Schemas

Rules and Schemas support custom validation strategies registered globally:

```php
// Register custom strategy
DataVerify::global()->register(new SiretStrategy());

// Use in a rule
DataVerify::registerRules('frenchCompany')
    ->required
    ->siret;

// Or use in a schema
DataVerify::registerSchema('invoice')
    ->field('company')->required->array
        ->subfield('siret')->required->siret;
```

### IDE Autocompletion

When IDE helper is enabled, registered rules and schemas get autocompletion:

```php
// Enable in development
if (getenv('APP_ENV') === 'development') {
    DataVerify::enableIdeHelper();
}

// Load your rules/schemas
DataVerify::loadRulesFrom(__DIR__ . '/config/rules');
DataVerify::loadSchemasFrom(__DIR__ . '/src/Schemas', 'App\\Schemas');

// IDE now autocompletes:
$dv->field('x')->rule->strongPassword;  // ✨ Autocomplete
$dv->schema->user;                       // ✨ Autocomplete
```

---

## Rules vs Schemas

| Feature | Rules | Schemas |
|---------|-------|---------|
| **Field declarations** | ❌ No | ✅ Yes |
| **Field-agnostic** | ✅ Yes | ❌ No |
| **Conditional logic** | ❌ No | ✅ Yes |
| **Chainable** | ✅ Multiple rules | ❌ One schema only |
| **Use in other constructs** | ✅ In Schemas | ❌ Standalone |
| **Best for** | Common patterns | API endpoints |

### When to Use Rules

✅ **Use Rules when:**
- Pattern applies to multiple fields
- No conditional logic needed
- Want to chain multiple validation sets
- Building a library of common validations

**Examples:**
```php
// Email validation
->rule('email')

// Password strength
->rule('strongPassword')

// Username format
->rule('username')
```

### When to Use Schemas

✅ **Use Schemas when:**
- Validating complete objects/structures
- Need conditional logic (POST vs PATCH)
- Fixed field structure
- API endpoint validation

**Examples:**
```php
// API endpoint
->schema('userApi')

// Form validation
->schema('contactForm')

// Complex object
->schema('orderWithItems')
```

---

## Best Practices

### 1. **Register Once, Use Many Times**

```php
// In bootstrap/config file
DataVerify::registerRules('strongPassword')->minLength(12)->containsUpper;
DataVerify::registerSchema('userApi')->field('user')->required->object;

// In controllers/handlers
$dv = new DataVerify($data);
$dv->schema('userApi');
```

### 2. **Naming Conventions**

```php
// Rules: lowercase or camelCase
DataVerify::registerRules('strongPassword')
DataVerify::registerRules('email')

// Schemas: PascalCase or descriptive
DataVerify::registerSchema('UserApi')
DataVerify::registerSchema('userCreateOrPatch')
```

### 3. **Compose Rules into Schemas**

```php
// Build reusable rules
DataVerify::registerRules('email')->email->disposableEmail;
DataVerify::registerRules('strongPassword')->minLength(12)->containsUpper;

// Compose into schema
DataVerify::registerSchema('userApi')
    ->field('user')->required->object
        ->subfield('email')->rule('email')
        ->subfield('password')->rule('strongPassword');
```

### 4. **Avoid Over-Engineering**

```php
// ❌ Too granular
DataVerify::registerRules('min12')->minLength(12);
DataVerify::registerRules('hasUpper')->containsUpper;

// ✅ Right level
DataVerify::registerRules('strongPassword')
    ->minLength(12)
    ->containsUpper
    ->containsLower;
```

### 5. **Document Business Rules**

```php
// Clear naming and comments
DataVerify::registerRules('pciCompliantPassword')
    ->minLength(8)
    ->containsUpper
    ->containsLower
    ->containsNumber
    ->containsSpecialCharacter;
// PCI DSS 3.2.1 requirement for password complexity
```

---

## Common Use Cases

### API REST Validation

```php
DataVerify::registerRules('email')->email->disposableEmail;
DataVerify::registerRules('password')->minLength(12)->containsUpper->containsLower;

DataVerify::registerSchema('userEndpoint')
    ->field('user')->required->object
        ->subfield('email')->rule('email')
            ->when('user.id', '!=', null)->then->required
        ->subfield('password')->rule('password')
            ->when('user.id', '!=', null)->then->required;
```

### Form Validation

```php
DataVerify::registerSchema('contactForm')
    ->field('name')->required->string->minLength(2)
    ->field('email')->required->email
    ->field('phone')
        ->when('contact_method', '=', 'phone')
            ->then->required->regex('/^\+?[1-9]\d{1,14}$/');
```

### Multi-Step Forms

```php
// Step 1: Basic info
DataVerify::registerSchema('step1')
    ->field('email')->required->email
    ->field('password')->required->minLength(12);

// Step 2: Profile
DataVerify::registerSchema('step2')
    ->field('name')->required->string
    ->field('birthdate')->required->date;

// Usage
$dv1 = new DataVerify($step1Data);
$dv1->schema('step1');

$dv2 = new DataVerify($step2Data);
$dv2->schema('step2');
```

### Nested Objects

```php
DataVerify::registerRules('address')
    ->required->string->minLength(5);

DataVerify::registerSchema('orderWithShipping')
    ->field('order')->required->object
        ->subfield('items')->required->array
        ->subfield('shipping')->required->object
            ->subfield('shipping', 'address')->rule('address')
            ->subfield('shipping', 'city')->required->string
            ->subfield('shipping', 'zipcode')->required->regex('/^\d{5}$/');
```

---

## Error Handling

### Rule Not Found

```php
$dv->field('password')->rule('nonexistent');
// Throws: LogicException: "Rule 'nonexistent' not found"
```

### Schema Not Found

```php
$dv->schema('nonexistent');
// Throws: LogicException: "Schema 'nonexistent' not found"
```

### Duplicate Registration

```php
DataVerify::registerRules('test')->minLength(5);
DataVerify::registerRules('test')->maxLength(10);
// Throws: LogicException: "Rule 'test' is already registered"
```

### Multiple Schemas

```php
$dv->schema('schema1');
$dv->schema('schema2');
// Throws: LogicException: "Cannot apply schema 'schema2': A schema or fields have already been defined"
```

---

## See Also

- **[Conditional Validation](CONDITIONAL_VALIDATION.md)** - Using when/then in schemas
- **[Custom Strategies](CUSTOM_STRATEGIES.md)** - Creating custom validations for rules
- **[Validations](VALIDATIONS.md)** - All available validation rules
- **[Error Handling](ERROR_HANDLING.md)** - Working with validation errors

---

**Navigation:** [Validations](VALIDATIONS.md) | [Conditional Validation](CONDITIONAL_VALIDATION.md) | **Rules & Schemas** | [Custom Strategies](CUSTOM_STRATEGIES.md) | [Error Handling](ERROR_HANDLING.md) | [Internationalization](INTERNATIONALIZATION.md) | [Benchmarks](BENCHMARK.md)

**[← Back to Main Documentation](../README.md)**