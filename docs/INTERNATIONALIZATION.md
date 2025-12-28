# Internationalization (i18n)

DataVerify includes a built-in translation system for validation error messages. Built-in languages (English, French) are automatically loaded - just set the locale.

## Table of Contents

- [Quick Start](#quick-start)
- [Built-in Languages](#built-in-languages)
- [Custom Translation Files](#custom-translation-files)
- [Inline Custom Translations](#inline-custom-translations)
- [Translation Placeholders](#translation-placeholders)
- [Custom Translator](#custom-translator)
- [Best Practices](#best-practices)

---

## Quick Start

```php
use Gravity\DataVerify;

$data = new stdClass();
$data->email = 'invalid';

$dv = new DataVerify($data);
$dv->setLocale('fr');  // Switch to French

$dv->field('email')->required->email;

if (!$dv->verify()) {
    echo $dv->getErrors()[0]['message'];
    // "Le champ email doit être une adresse email valide"
}
```

---

## Built-in Languages

<details>
<summary><strong>Available Built-in Locales</strong></summary>

DataVerify comes with built-in translations that are **automatically loaded**:

- **English (`en`)** - Default locale
- **French (`fr`)** - Ready to use

```php
$dv = new DataVerify($data);

// English (default)
$dv->field('email')->required;
// "The field email is required"

// French
$dv->setLocale('fr');
$dv->field('email')->required;
// "Le champ email est requis"
```

**Note:** Built-in translations are in `src/Locales/` (no dependencies).

</details>

---

## Custom Translation Files

<details>
<summary><strong>PHP Translation Files (Recommended)</strong></summary>

```php
<?php
// locales/es/validations.php
return [
    'validation.required' => 'El campo {field} es obligatorio',
    'validation.email' => 'El campo {field} debe ser un email válido',
    'validation.minLength' => 'El campo {field} debe tener al menos {min} caracteres',
];
```

**Load and use:**

```php
$dv->loadLocale('es', __DIR__ . '/locales/es/validations.php');
$dv->setLocale('es');
```

**Why PHP files?** Fast, zero dependencies, native support.

</details>

<details>
<summary><strong>YAML Files (Requires symfony/yaml)</strong></summary>

```yaml
# locales/es/validations.yml
validation.required: "El campo {field} es obligatorio"
validation.email: "El campo {field} debe ser un email válido"
```

```php
$dv->loadLocale('es', __DIR__ . '/locales/es/validations.yml');
$dv->setLocale('es');
```

**Note:** Requires `composer require symfony/yaml`

</details>

<details>
<summary><strong>Recommended File Structure</strong></summary>

```
app/locales/
├── en/validations.php
├── fr/validations.php
└── es/validations.php
```

**Load multiple files per locale:**

```php
$dv
    ->loadLocale('es', __DIR__ . '/locales/es/validations.php')
    ->loadLocale('es', __DIR__ . '/locales/es/custom.php')
    ->setLocale('es');
```

</details>

---

## Inline Custom Translations

<details>
<summary><strong>Adding Translations Directly in Code</strong></summary>

```php
$dv->addTranslations([
    'validation.strong_password' => 'The {field} must be at least {minLength} characters with uppercase, lowercase, number, and special character'
], 'en');

$dv->addTranslations([
    'validation.strong_password' => 'Le {field} doit contenir au moins {minLength} caractères avec majuscule, minuscule, chiffre et caractère spécial'
], 'fr');
```

**Use for:** Custom strategies, quick prototyping, one-off translations.

</details>

<details>
<summary><strong>Overriding Built-in Messages</strong></summary>

```php
$dv->addTranslations([
    'validation.required' => '{field} is absolutely mandatory!'
], 'en');

$dv->field('email')->required;
// "email is absolutely mandatory!"
```

</details>

---

## Translation Placeholders

<details>
<summary><strong>Built-in Placeholders</strong></summary>

| Placeholder | Used In | Example |
|-------------|---------|---------|
| `{field}` | All validations | Field name or alias |
| `{min}` | `minLength`, `between`, `greaterThan` | Minimum value |
| `{max}` | `maxLength`, `between`, `lowerThan` | Maximum value |
| `{mime}` | `fileMime` | MIME type(s) |
| `{value}` | Custom messages | Actual field value |

```php
$dv->field('password')->minLength(8);
// "The field password must be at least 8 characters"
//                                         ^ {min} auto-replaced
```

</details>

<details>
<summary><strong>Automatic Parameter Mapping</strong></summary>

**DataVerify automatically discovers parameter names from `handler()` methods and makes them available as placeholders.**

```php
use Gravity\Validations\ValidationStrategy;

class RangeStrategy extends ValidationStrategy
{
    public function getName(): string
    {
        return 'in_range';
    }
    
    protected function handler(
        mixed $value,
        int $min,    // Becomes {min} placeholder
        int $max     // Becomes {max} placeholder
    ): bool {
        return $value >= $min && $value <= $max;
    }
}

// Translation automatically recognizes {min} and {max}
$dv->addTranslations([
    'validation.in_range' => 'The {field} must be between {min} and {max}'
], 'en');

// Usage
$dv->field('age')->in_range(18, 65);
// Error: "The field age must be between 18 and 65"
//         {min} and {max} auto-populated from parameters
```

**How it works:**
- Reflection extracts parameter names from your `handler()` method
- Parameter names become placeholders (e.g., `$threshold` → `{threshold}`)
- Values are automatically mapped when validation is called

**Best practice - Use standard names:**

```php
// ✅ Good - Matches conventions
protected function handler(mixed $value, int $min, int $max): bool
{
    // {min} and {max} in translations
}

// ❌ Works but unclear
protected function handler(mixed $value, int $firstLimit, int $secondLimit): bool
{
    // {firstLimit} and {secondLimit} - less intuitive
}
```

**Standard names:** `{min}`, `{max}`, `{mime}`, `{format}`, `{allowed}`, `{forbidden}`

</details>

<details>
<summary><strong>Using Aliases</strong></summary>

```php
// Without alias
$dv->field('user_email')->required;
// "The field user_email is required"

// With alias
$dv->field('user_email')->alias('Email Address')->required;
// "The field Email Address is required"
```

</details>

<details>
<summary><strong>Custom Error Messages (Bypass Translations)</strong></summary>

```php
$dv->field('email')
    ->required
    ->email
    ->errorMessage('Please provide a valid professional email address');
```

</details>

---

## Custom Translator

<details>
<summary><strong>Extending Translator Class</strong></summary>

For advanced needs (database, API, cache):

```php
use Gravity\Translation\Translator;

class DatabaseTranslator extends Translator
{
    private PDO $pdo;
    
    public function __construct(PDO $pdo, string $locale = 'en')
    {
        parent::__construct($locale);
        $this->pdo = $pdo;
    }
    
    public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        $locale = $locale ?? $this->getLocale();
        $domain = $domain ?? 'messages';
        
        // Load from database
        $message = $this->loadFromDb($id, $locale, $domain);
        
        if ($message === null) {
            // Fallback to parent (handles fallback locale)
            return parent::trans($id, $parameters, $domain, $locale);
        }
        
        // Use parent's placeholder replacement
        return $this->replacePlaceholders($message, $parameters);
    }
    
    private function loadFromDb(string $id, string $locale, string $domain): ?string
    {
        $stmt = $this->pdo->prepare(
            'SELECT message FROM translations WHERE id = ? AND locale = ? AND domain = ?'
        );
        $stmt->execute([$id, $locale, $domain]);
        return $stmt->fetchColumn() ?: null;
    }
}

$dv->setTranslator(new DatabaseTranslator($pdo, 'fr'));
```

</details>

---

## Best Practices

<details>
<summary><strong>Performance Tips</strong></summary>

**Load at bootstrap, switch on demand:**

```php
// bootstrap.php - Load once
$dv->loadLocale('es', __DIR__ . '/locales/es.php');
$dv->loadLocale('de', __DIR__ . '/locales/de.php');

// Later - instant switch (no I/O)
$dv->setLocale('es');
```

</details>

<details>
<summary><strong>Organization</strong></summary>

**Use translation files over inline:**

```php
// ✅ Organized, reusable
$dv->loadLocale('es', __DIR__ . '/locales/es.php');

// ❌ Scattered, hard to maintain
$dv->addTranslations([...], 'es');
```

**Leverage placeholders:**

```php
// ✅ Dynamic
'validation.between' => 'Between {min} and {max}'

// ❌ Hardcoded
'validation.between' => 'Between 1 and 100'
```

</details>

<details>
<summary><strong>Custom Strategies</strong></summary>

**Use named parameters in handler():**

```php
use Gravity\Validations\ValidationStrategy;

// ✅ Clean - parameter names become placeholders
class MyStrategy extends ValidationStrategy
{
    protected function handler(mixed $value, int $min, int $max): bool
    {
        return $value >= $min && $value <= $max;
    }
}

// Translation
'validation.my_validation' => 'Between {min} and {max}'  // Clear and automatic
```

</details>

---

## See Also

- **[Custom Strategies](CUSTOM_STRATEGIES.md)** - Parameter mapping for custom validations
- **[Error Handling](ERROR_HANDLING.md)** - Working with translated errors
- **[Validation Rules](VALIDATIONS.md)** - All validation rules
