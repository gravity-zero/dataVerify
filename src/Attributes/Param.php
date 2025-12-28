<?php

namespace Gravity\Attributes;

use Attribute;

/**
 * Documents a validation parameter
 * 
 * Used on method parameters to provide metadata for documentation
 * 
 * @example
 * protected function validateMinLength(
 *     $value,
 *     #[Param('Minimum length required', example: 8)]
 *     int $length
 * ): bool { ... }
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class Param
{
    public function __construct(
        public readonly string $description,
        public readonly mixed $example = null
    ) {}
}
