# Contributing to DataVerify

Thank you for considering contributing to DataVerify! This document outlines the process and guidelines for contributing.

## Code of Conduct

By participating in this project, you agree to maintain a respectful and inclusive environment for everyone.

## How Can I Contribute?

### Reporting Bugs

Before creating a bug report, please check existing issues to avoid duplicates.

**When submitting a bug report, include:**
- A clear, descriptive title
- Steps to reproduce the issue
- Expected vs actual behavior
- PHP version and environment details
- Code samples demonstrating the issue

**Example:**
```markdown
**Bug:** Conditional validation fails with nested subfields

**Steps to reproduce:**
1. Create data with nested structure
2. Apply conditional validation with `when()->then` on subfield
3. Call verify()

**Expected:** Validation passes when condition is met
**Actual:** Throws LogicException

**Environment:**
- PHP 8.1.15
- DataVerify 1.0.0
```

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues.

**Include:**
- Clear use case for the enhancement
- Why existing functionality doesn't cover this need
- Code examples of the proposed API
- Whether you're willing to implement it

**Example:**
```markdown
**Enhancement:** Add `url` validation rule

**Use case:** Validate URLs in API requests without regex

**Proposed API:**
```php
$dv->field('website')->required->url;
```

**Why needed:** Common validation, currently requires regex pattern
```

### Pull Requests

1. **Fork the repository** and create your branch from `master`
2. **Follow code style** (see below)
3. **Add tests** for new functionality
4. **Update documentation** if needed
5. **Ensure tests pass** (`make test`)
6. **Run mutation tests** (`make mutation`)
7. **Submit pull request**

## Development Setup

```bash
# Clone your fork
git clone https://github.com/YOUR_USERNAME/dataverify.git
cd dataverify

# Install dependencies
composer install

# Run tests
make test

# Run mutation tests
make mutation

# Run benchmarks
make benchmark
```

## Coding Standards

### PHP Code Style

- **PSR-12** coding standard
- **PHP 8.1+** features encouraged (typed properties, enums, attributes)
- **Type hints** required for all parameters and return types
- **DocBlocks** required for public methods and complex logic

**Example:**
```php
/**
 * Validates email addresses with disposable domain checking
 * 
 * @param mixed $value The value to validate
 * @param array $disposables Custom disposable domains
 * @return bool True if valid email and not disposable
 */
protected function handler(mixed $value, array $disposables = []): bool
{
    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    // Implementation...
}
```

### Architecture Principles

- **SOLID principles** - especially Single Responsibility
- **No God classes** - split large classes into focused components
- **Immutability** where possible
- **Dependency injection** over static calls
- **Interfaces** for extensibility points

### Validation Strategy Guidelines

When adding new validation rules:

```php
<?php
namespace Gravity\Validations\YourCategory;

use Gravity\Validations\ValidationStrategy;
use Gravity\Attributes\{ValidationRule, Param};

#[ValidationRule(
    name: 'yourRule',
    description: 'Clear description of what this validates',
    category: 'YourCategory',
    examples: ['$verifier->field("test")->yourRule']
)]
class YourRuleValidation extends ValidationStrategy
{
    public function getName(): string
    {
        return 'yourRule';
    }

    /**
     * @param mixed $value The value to validate
     * @param mixed ...$args Optional parameters
     */
    protected function handler(mixed $value, mixed ...$args): bool
    {
        // Validation logic
        return true;
    }
}
```

**Requirements:**
1. ✅ Use `#[ValidationRule]` attribute with all metadata
2. ✅ Extend `ValidationStrategy`
3. ✅ Implement `getName()` returning rule name
4. ✅ Implement `handler()` with typed parameters
5. ✅ Add comprehensive tests
6. ✅ Add translation keys for error messages

## Testing Guidelines

### Test Coverage Requirements

- **New features:** 100% code coverage
- **Bug fixes:** Regression test required
- **Mutation score:** Maintain or improve (target 72%+)

### Writing Tests

```php
public function testYourFeature(): void
{
    // Arrange
    $data = (object)['field' => 'value'];
    $dv = new DataVerify($data);
    
    // Act
    $dv->field('field')->yourValidation;
    
    // Assert
    $this->assertTrue($dv->verify());
}

public function testYourFeatureFailure(): void
{
    $data = (object)['field' => 'invalid'];
    $dv = new DataVerify($data);
    $dv->field('field')->yourValidation;
    
    $this->assertFalse($dv->verify());
    
    $errors = $dv->getErrors();
    $this->assertNotEmpty($errors);
    $this->assertStringContainsString('expected error message', $errors[0]['message']);
}
```

### Test Organization

- **Unit tests:** Test individual classes/methods in isolation
- **Integration tests:** Test component interactions
- **Edge cases:** Test null, empty strings, 0, false, boundary values

## Performance Considerations

- **Benchmark new features** using PHPBench
- **Avoid Reflection in hot paths** (cache it instead)
- **Lazy load** expensive operations
- **Profile before optimizing** (`make benchmark`)

**Example - DO:**
```php
// Cache reflection (done once per class)
private static array $reflectionCache = [];

if (!isset(self::$reflectionCache[$class])) {
    self::$reflectionCache[$class] = new ReflectionMethod($this, 'handler');
}
```

**Example - DON'T:**
```php
// Creates new Reflection on every call
$reflection = new ReflectionMethod($this, 'handler');
```

## Documentation

### Update Documentation When:

- Adding new validation rules → Update `docs/VALIDATIONS.md`
- Adding features → Update relevant guide in `docs/`
- Changing API → Update `README.md` examples
- Adding translations → Document in `docs/INTERNATIONALIZATION.md`

### Documentation Style

- **Code examples** for every feature
- **Real-world use cases** when possible
- **Clear explanations** over technical jargon
- **Keep examples runnable** and tested

## Commit Messages

Use conventional commits format:

```
<type>(<scope>): <description>

[optional body]

[optional footer]
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation only
- `refactor`: Code refactoring
- `test`: Adding tests
- `perf`: Performance improvement
- `chore`: Maintenance tasks

**Examples:**
```
feat(validation): add url validation rule

Adds URL validation using filter_var with FILTER_VALIDATE_URL.
Supports optional scheme validation.

Closes #123
```

```
fix(conditional): handle null values in when() clause

When condition field was null, when() threw TypeError.
Now properly handles null as a valid comparison value.

Fixes #456
```

## Pull Request Process

1. **Update CHANGELOG.md** with your changes
2. **Ensure all tests pass** locally
3. **Update documentation** if needed
4. **Request review** from maintainers
5. **Address feedback** promptly
6. **Squash commits** if requested

### PR Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] Tests added/updated
- [ ] All tests passing
- [ ] Mutation score maintained/improved

## Checklist
- [ ] Code follows project style
- [ ] Documentation updated
- [ ] CHANGELOG.md updated
- [ ] No breaking changes (or documented)
```

## Release Process

Releases are managed by maintainers following semantic versioning:

- **Major (x.0.0):** Breaking changes
- **Minor (1.x.0):** New features (backward compatible)
- **Patch (1.0.x):** Bug fixes

## Questions?

- **GitHub Issues:** For bugs and feature requests
- **GitHub Discussions:** For questions and community help

## Recognition

Contributors are recognized in:
- CHANGELOG.md for their contributions
- GitHub contributors page
- Special thanks for significant contributions

---

Thank you for contributing to DataVerify! 🎉
