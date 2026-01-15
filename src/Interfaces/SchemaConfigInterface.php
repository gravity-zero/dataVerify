<?php

namespace Gravity\Interfaces;

use Gravity\Schema\SchemaBuilder;

/**
 * Interface for schema configuration classes
 * 
 * Implement this interface to define schemas as classes that can be
 * auto-discovered and loaded from a directory.
 * 
 * Example:
 * ```php
 * class UserSchema implements SchemaConfigInterface
 * {
 *     public function getName(): string
 *     {
 *         return 'user';
 *     }
 * 
 *     public function define(SchemaBuilder $builder): void
 *     {
 *         $builder
 *             ->field('email')->required->email
 *             ->field('name')->required->string->minLength(2);
 *     }
 * }
 * ```
 */
interface SchemaConfigInterface
{
    /**
     * Get the unique name for this schema
     */
    public function getName(): string;

    /**
     * Define the schema structure using the builder
     * 
     * @param SchemaBuilder $builder Pre-initialized builder with the schema name
     */
    public function define(SchemaBuilder $builder): void;
}
