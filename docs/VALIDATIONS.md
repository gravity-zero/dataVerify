# Validation Rules Reference

Complete reference of all **30** built-in validation rules.

## Table of Contents

- [Comparison](#comparison) (2 rules)
- [Core](#core) (1 rules)
- [Date](#date) (1 rules)
- [File](#file) (2 rules)
- [Numeric](#numeric) (3 rules)
- [String](#string) (14 rules)
- [Type](#type) (7 rules)

---

## Comparison

<details>
<summary><code>notIn</code> - Validates that a value does not exist in a forbidden list or as an obj...</summary>

**Description:**

Validates that a value does not exist in a forbidden list or as an object property

**Parameters:**

<table>
<tr><th>Name</th><th>Type</th><th>Required</th><th>Description</th></tr>
<tr><td><code>forbidden</code></td><td><code>object|array</code></td><td align="center">✓</td><td>Array or object of forbidden values<br><em>Example: <code>["admin","root"]</code></em></td></tr>
</table>

**Usage:**

```php
$verifier->field("test")->notIn(["admin", "root"])
```

</details>

<details>
<summary><code>in</code> - Validates that a value exists in an allowed list or as an object prope...</summary>

**Description:**

Validates that a value exists in an allowed list or as an object property

**Parameters:**

<table>
<tr><th>Name</th><th>Type</th><th>Required</th><th>Description</th></tr>
<tr><td><code>allowed</code></td><td><code>object|array</code></td><td align="center">✓</td><td>Array or object of allowed values<br><em>Example: <code>["active","pending"]</code></em></td></tr>
</table>

**Usage:**

```php
$verifier->field("test")->in(["active", "pending"])
```

</details>

## Core

<details>
<summary><code>required</code> - Validates that a field is not empty. Objects are cast to arrays to che...</summary>

**Description:**

Validates that a field is not empty. Objects are cast to arrays to check emptiness.

**Usage:**

```php
$verifier->field("test")->required
```

</details>

## Date

<details>
<summary><code>date</code> - Validates that a value is a valid date in the specified format. Perfor...</summary>

**Description:**

Validates that a value is a valid date in the specified format. Performs strict validation

**Parameters:**

<table>
<tr><th>Name</th><th>Type</th><th>Required</th><th>Description</th></tr>
<tr><td><code>format</code></td><td><code>string</code></td><td align="center">✗</td><td>Date format<br><em>Example: <code>'Y-m-d'</code></em><br><em>Default: <code>'Y-m-d'</code></em></td></tr>
</table>

**Usage:**

```php
$verifier->field("test")->date("Y-m-d")
```

</details>

## File

<details>
<summary><code>fileMime</code> - Validates that a file has an allowed MIME type</summary>

**Parameters:**

<table>
<tr><th>Name</th><th>Type</th><th>Required</th><th>Description</th></tr>
<tr><td><code>mime</code></td><td><code>array|string</code></td><td align="center">✓</td><td>Allowed MIME type(s)<br><em>Example: <code>'image/jpeg'</code></em></td></tr>
</table>

**Usage:**

```php
$verifier->field("test")->fileMime("image/jpeg")
```

</details>

<details>
<summary><code>fileExists</code> - Validates that a file exists at the given path</summary>

**Usage:**

```php
$verifier->field("test")->fileExists
```

</details>

## Numeric

<details>
<summary><code>between</code> - Validates that a value is between two bounds. Supports numeric values ...</summary>

**Description:**

Validates that a value is between two bounds. Supports numeric values and DateTime objects

**Parameters:**

<table>
<tr><th>Name</th><th>Type</th><th>Required</th><th>Description</th></tr>
<tr><td><code>min</code></td><td><code>DateTime|string|int|float</code></td><td align="center">✓</td><td>Minimum value (inclusive)<br><em>Example: <code>18</code></em></td></tr>
<tr><td><code>max</code></td><td><code>DateTime|string|int|float</code></td><td align="center">✓</td><td>Maximum value (inclusive)<br><em>Example: <code>65</code></em></td></tr>
</table>

**Usage:**

```php
$verifier->field("test")->between(18, 65)
```

</details>

<details>
<summary><code>greaterThan</code> - Validates that a value is greater than a specified limit</summary>

**Parameters:**

<table>
<tr><th>Name</th><th>Type</th><th>Required</th><th>Description</th></tr>
<tr><td><code>min</code></td><td><code>mixed</code></td><td align="center">✓</td><td>Value must be greater than this<br><em>Example: <code>18</code></em></td></tr>
</table>

**Usage:**

```php
$verifier->field("test")->greaterThan(18)
```

</details>

<details>
<summary><code>lowerThan</code> - Validates that a value is less than a specified limit</summary>

**Parameters:**

<table>
<tr><th>Name</th><th>Type</th><th>Required</th><th>Description</th></tr>
<tr><td><code>max</code></td><td><code>mixed</code></td><td align="center">✓</td><td>Value must be less than this<br><em>Example: <code>65</code></em></td></tr>
</table>

**Usage:**

```php
$verifier->field("test")->lowerThan(65)
```

</details>

## String

<details>
<summary><code>alphanumeric</code> - Validates that a value contains only alphanumeric characters</summary>

**Usage:**

```php
$verifier->field("test")->alphanumeric
```

</details>

<details>
<summary><code>maxLength</code> - Validates that a string does not exceed maximum length</summary>

**Parameters:**

<table>
<tr><th>Name</th><th>Type</th><th>Required</th><th>Description</th></tr>
<tr><td><code>max</code></td><td><code>int</code></td><td align="center">✓</td><td>Maximum length allowed<br><em>Example: <code>20</code></em></td></tr>
</table>

**Usage:**

```php
$verifier->field("test")->maxLength(20)
```

</details>

<details>
<summary><code>disposableEmail</code> - Validates that an email is not from a disposable domain</summary>

**Parameters:**

<table>
<tr><th>Name</th><th>Type</th><th>Required</th><th>Description</th></tr>
<tr><td><code>disposables</code></td><td><code>array</code></td><td align="center">✗</td><td>Array of disposable domain patterns<br><em>Example: <code>[]</code></em><br><em>Default: <code>[]</code></em></td></tr>
</table>

**Usage:**

```php
$verifier->field("test")->disposableEmail([])
```

</details>

<details>
<summary><code>disposableUrlDomain</code> - Validates that a URL is not from a disposable/temporary domain (URL sh...</summary>

**Description:**

Validates that a URL is not from a disposable/temporary domain (URL shorteners, free hosting, etc.)

**Parameters:**

<table>
<tr><th>Name</th><th>Type</th><th>Required</th><th>Description</th></tr>
<tr><td><code>disposables</code></td><td><code>array</code></td><td align="center">✗</td><td>Array of disposable domain patterns<br><em>Example: <code>[]</code></em><br><em>Default: <code>[]</code></em></td></tr>
</table>

**Usage:**

```php
$verifier->field("website")->disposableUrlDomain
```

```php
$verifier->field("website")->disposableUrlDomain(["bit.ly", "tinyurl.com"])
```

</details>

<details>
<summary><code>url</code> - Validates that a value is a valid URL with configurable schemes and TL...</summary>

**Description:**

Validates that a value is a valid URL with configurable schemes and TLD requirement

**Parameters:**

<table>
<tr><th>Name</th><th>Type</th><th>Required</th><th>Description</th></tr>
<tr><td><code>schemes</code></td><td><code>array</code></td><td align="center">✗</td><td>Allowed URL schemes<br><em>Example: <code>["http","https"]</code></em><br><em>Default: <code>["http","https"]</code></em></td></tr>
<tr><td><code>requireTld</code></td><td><code>bool</code></td><td align="center">✗</td><td>Require a top-level domain (.com|.org|.net|...)<br><em>Example: <code>true</code></em><br><em>Default: <code>true</code></em></td></tr>
</table>

**Usage:**

```php
$verifier->field("website")->url
```

```php
$verifier->field("api")->url(["http", "https", "ws", "wss"])
```

```php
$verifier->field("intranet")->url(["http"], requireTld: false)
```

</details>

<details>
<summary><code>containsNumber</code> - Validates that a string contains at least one digit</summary>

**Usage:**

```php
$verifier->field("test")->containsNumber
```

</details>

<details>
<summary><code>notAlphanumeric</code> - Validates that a value contains non-alphanumeric characters</summary>

**Usage:**

```php
$verifier->field("test")->notAlphanumeric
```

</details>

<details>
<summary><code>minLength</code> - Validates that a string has a minimum length</summary>

**Parameters:**

<table>
<tr><th>Name</th><th>Type</th><th>Required</th><th>Description</th></tr>
<tr><td><code>min</code></td><td><code>int</code></td><td align="center">✓</td><td>Minimum length required<br><em>Example: <code>8</code></em></td></tr>
</table>

**Usage:**

```php
$verifier->field("test")->minLength(8)
```

</details>

<details>
<summary><code>containsUpper</code> - Validates that a string contains at least one uppercase letter</summary>

**Usage:**

```php
$verifier->field("test")->containsUpper
```

</details>

<details>
<summary><code>email</code> - Validates that a value is a valid email address</summary>

**Usage:**

```php
$verifier->field("test")->email
```

</details>

<details>
<summary><code>regex</code> - Validates that a value matches a regular expression pattern. Warnings ...</summary>

**Description:**

Validates that a value matches a regular expression pattern. Warnings are suppressed

**Parameters:**

<table>
<tr><th>Name</th><th>Type</th><th>Required</th><th>Description</th></tr>
<tr><td><code>pattern</code></td><td><code>string</code></td><td align="center">✓</td><td>Regular expression pattern<br><em>Example: <code>'/^[A-Z]+$/'</code></em></td></tr>
</table>

**Usage:**

```php
$verifier->field("test")->regex("/^[A-Z]+$/")
```

</details>

<details>
<summary><code>ipAddress</code> - Validates that a value is a valid IP address (IPv4 or IPv6)</summary>

**Usage:**

```php
$verifier->field("test")->ipAddress
```

</details>

<details>
<summary><code>containsLower</code> - Validates that a string contains at least one lowercase letter</summary>

**Usage:**

```php
$verifier->field("test")->containsLower
```

</details>

<details>
<summary><code>containsSpecialCharacter</code> - Validates that a string contains at least one special character</summary>

**Usage:**

```php
$verifier->field("test")->containsSpecialCharacter
```

</details>

## Type

<details>
<summary><code>string</code> - Validates that a value is a string</summary>

**Usage:**

```php
$verifier->field("test")->string
```

</details>

<details>
<summary><code>int</code> - Validates that a value is an integer. In strict mode (default), only t...</summary>

**Description:**

Validates that a value is an integer. In strict mode (default), only true integers are accepted

**Parameters:**

<table>
<tr><th>Name</th><th>Type</th><th>Required</th><th>Description</th></tr>
<tr><td><code>strict</code></td><td><code>bool</code></td><td align="center">✗</td><td>Strict mode: true for integers only<br><em>Example: <code>true</code></em><br><em>Default: <code>true</code></em></td></tr>
</table>

**Usage:**

```php
$verifier->field("test")->int(true)
```

</details>

<details>
<summary><code>array</code> - Validates that a value is an array</summary>

**Usage:**

```php
$verifier->field("test")->array
```

</details>

<details>
<summary><code>object</code> - Validates that a value is an object</summary>

**Usage:**

```php
$verifier->field("test")->object
```

</details>

<details>
<summary><code>boolean</code> - Validates that a value is a boolean. In strict mode (default), only tr...</summary>

**Description:**

Validates that a value is a boolean. In strict mode (default), only true/false are accepted

**Parameters:**

<table>
<tr><th>Name</th><th>Type</th><th>Required</th><th>Description</th></tr>
<tr><td><code>strict</code></td><td><code>bool</code></td><td align="center">✗</td><td>Strict mode: true for booleans only<br><em>Example: <code>true</code></em><br><em>Default: <code>true</code></em></td></tr>
</table>

**Usage:**

```php
$verifier->field("test")->boolean(true)
```

</details>

<details>
<summary><code>json</code> - Validates that a string is valid JSON</summary>

**Usage:**

```php
$verifier->field("test")->json
```

</details>

<details>
<summary><code>numeric</code> - Validates that a value is numeric</summary>

**Usage:**

```php
$verifier->field("test")->numeric
```

</details>

---

## See Also

- **[Error Handling](ERROR_HANDLING.md)** - Working with validation errors
- **[Conditional Validation](CONDITIONAL_VALIDATION.md)** - Apply rules conditionally
- **[Custom Strategies](CUSTOM_STRATEGIES.md)** - Create your own validations
- **[Internationalization](INTERNATIONALIZATION.md)** - Translate error messages
