<?php

namespace Gravity\Engine;

use Gravity\Collections\FieldCollection;
use Gravity\Registry\SchemaRegistry;

/**
 * Handles application of registered schemas to field collections
 */
class SchemaEngine
{
    public function __construct(
        private SchemaRegistry $schemaRegistry
    ) {}

    /**
     * Apply a schema to the field collection
     * 
     * @throws \LogicException If schema not found or fields already defined
     */
    public function apply(FieldCollection $fields, string $schemaName): void
    {
        $schema = $this->schemaRegistry->get($schemaName);
        
        if ($schema === null) {
            throw new \LogicException("Schema '{$schemaName}' not found");
        }

        if (!$fields->isEmpty()) {
            throw new \LogicException(
                "Cannot apply schema '{$schemaName}': A schema or fields have already been defined. " .
                "Only one schema can be applied per DataVerify instance."
            );
        }

        foreach ($schema->getFields() as $field) {
            $fields->add($field);
        }
    }
}