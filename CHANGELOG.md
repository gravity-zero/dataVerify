# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
