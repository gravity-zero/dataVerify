# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
