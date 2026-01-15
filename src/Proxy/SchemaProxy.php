<?php

namespace Gravity\Proxy;

use Gravity\DataVerify;
use Gravity\Registry\SchemaRegistry;
use Gravity\Collections\FieldCollection;

/**
 * Proxy for applying schemas with multiple syntaxes
 * 
 * Supports:
 * - $dv->schema("schemaName")
 * - $dv->schema->schemaName
 * - $dv->schema->schemaName()
 */
class SchemaProxy
{
    private DataVerify $dataVerify;
    private FieldCollection $fields;

    public function __construct(DataVerify $dataVerify, FieldCollection $fields)
    {
        $this->dataVerify = $dataVerify;
        $this->fields = $fields;
    }

    /**
     * Apply a schema via property access
     * 
     * Usage: ->schema->userApi
     */
    public function __get(string $schemaName): DataVerify
    {
        return $this->applySchema($schemaName);
    }

    /**
     * Apply a schema via method call
     * 
     * Usage: ->schema->userApi()
     */
    public function __call(string $schemaName, array $args): DataVerify
    {
        return $this->applySchema($schemaName);
    }

    /**
     * Apply a schema to the DataVerify instance
     */
    private function applySchema(string $schemaName): DataVerify
    {
        $schema = SchemaRegistry::instance()->get($schemaName);
        
        if ($schema === null) {
            throw new \LogicException("Schema '{$schemaName}' not found");
        }

        // Check if a schema was already applied
        if (!$this->fields->isEmpty()) {
            throw new \LogicException(
                "Cannot apply schema '{$schemaName}': A schema or fields have already been defined. " .
                "Only one schema can be applied per DataVerify instance."
            );
        }

        // Add all fields from the schema
        foreach ($schema->getFields() as $field) {
            $this->fields->add($field);
        }

        return $this->dataVerify;
    }
}
