# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-01-15
### Added
- **Reusable Rules** - Register field-agnostic validation patterns with `DataVerify::registerRules()`
- **Validation Schemas** - Define complete validation structures with `DataVerify::registerSchema()`
- Rules support 3 syntaxes: `->rule('ruleName')`, `->rule->ruleName`, `->rule->ruleName()`
- Schemas support 3 syntaxes: `->schema('schemaName')`, `->schema->schemaName`, `->schema->schemaName()`
- Schemas support conditional logic and rule composition
- **Load from directory** - `DataVerify::loadRulesFrom()` and `DataVerify::loadSchemasFrom()` for file-based organization
- **Auto-discovery from directory** - Load custom validation strategies automatically via `LazyValidationRegistry`
- Example files for rules and schemas in `/examples` including batch registration and performance patterns

### Changed
- **Deferred Conditional Evaluation** - Conditional validations now evaluate during `verify()` instead of at definition time for more intuitive rule composition

### Refactored
- LazyValidationRegistry now auto-discovers validation directories without hardcoded configuration
- Removed legacy `conditionalValidations` system in favor of unified `allValidations` structure
- Enhanced OpenAPI documentation with Rules & Schemas sections

### Performance
- Rules registration: 1.4μs P99 (20 rules: 20.6μs)
- Schema registration: 4.6μs P99 (10 schemas: 45.9μs)
- Rules application: 5.9μs P99 (comparable to inline)
- Schema application: 7.7μs P99 (37% faster than manual!)
- All validations remain <20μs P99

### Tests
- Add 2 Unit Test Class for Rules & Schema
- 616 tests, 1192 assertions, 0 failures
- Mutation score: 84% 

## [1.0.5] - 2026-01-06
### Tests
- Restructured test suite into Unit/Integration/Regression hierarchy
- Consolidated 7 duplicate tests across 4 test files
- 502 tests, 924 assertions, 0 failures

### Tooling
- Configured PHPStan level max, resolved initial PHPDoc issues
- Updated benchmark results (PHP 8.5.1 + OPcache)

### Documentation
- Updated README with new performance metrics
- Generated comprehensive PHPDoc annotations

## [1.0.4] - 2026-01-05
### Performance
- **Static Translator Cache** - Eliminates repeated file I/O across validation instances
- TranslationManager now uses process-wide cached translator instance
- Translation file loaded once per PHP process instead of per DataVerify instance

### Infrastructure
- All benchmarks now run with OPcache enabled for production-realistic results

## [1.0.3] - 2026-01-04
### Performance
- Lazy TranslationManager initialization - 45% performance improvement
- TranslationManager now created only when validation fails or explicitly configured
- Eliminates unnecessary I/O for successful validations (~90% of production cases)

## [1.0.2] - 2026-01-03
### Added
- New `url` validation rule with configurable schemes and TLD handling
- New `disposableUrlDomain` rule to detect disposable or temporary domains

### Fixed
- Conditional validations now execute the full validation chain after `then`, not only the first rule

## [1.0.1] - 2025-12-28
### Fixed
- Centralized conditional operator validation to prevent desynchronization between `ConditionalEngine` and `ValidationOrchestrator`
- Introduced `ConditionalOperator` enum for type-safe operator validation across conditional chains

## [1.0.0] - 2025-12-28
### Added
- Initial stable release
- Fluent validation interface with method chaining
- Batch and fail-fast validation modes
- Conditional validation using `when / and / or / then` syntax
- Support for custom validation strategies with global registry
- Subfield validation for nested objects and arrays
- Deep array nesting with index support (`subfield('0', 'items', '2')`)
- 40+ built-in validation rules across 7 categories
- Custom error messages per validation
- Field aliases for cleaner error reporting
- Built-in internationalization (EN, FR)
- Translation system with PHP and YAML loaders
- Custom translator interface

### Tooling
- Auto-generated documentation (Markdown, JSON, OpenAPI)
- Swagger UI generation
- IDE helper generation for custom strategies

### Quality
- Comprehensive test suite (350 tests, 670 assertions)
- Mutation testing with 72% MSI score
- Performance benchmarks (~50μs per validation, ~4.9MB memory)

### Infrastructure
- Zero production dependencies
- PSR-4 autoloading
- Extensive documentation and usage examples
