<?php

namespace Gravity\Attributes;

use Attribute;

/**
 * Marks a method as a validation rule
 * 
 * Can be used on:
 * - Protected methods in ValidationMethods class
 * - ValidationStrategyInterface implementations (optional)
 * 
 * @example
 * #[ValidationRule(
 *     name: 'email',
 *     description: 'Validates email format',
 *     category: 'String',
 *     examples: ['->field("email")->email']
 * )]
 * protected function validateEmail($value): bool { ... }
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class ValidationRule
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $description = null,
        public readonly ?string $category = null,
        public readonly array $examples = []
    ) {}
}
