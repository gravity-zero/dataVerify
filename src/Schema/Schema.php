<?php

namespace Gravity\Schema;

use Gravity\Handlers\{FieldHandler, SubFieldHandler};

/**
 * Schema - Stores complete validation structure with fields and subfields
 * 
 * Schemas include field declarations and can use conditional logic.
 */
class Schema
{
    private string $name;
    /** @var list<FieldHandler> */
    private array $fields = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Add a field handler
     */
    public function addField(FieldHandler $field): void
    {
        $this->fields[] = $field;
    }

    /**
     * Get all fields
     * 
     * @return list<FieldHandler>
     */
    public function getFields(): array
    {
        return $this->fields;
    }
}
