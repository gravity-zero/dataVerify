<?php

namespace Tests\Fixtures\Schemas;

/**
 * This class doesn't implement SchemaConfigInterface
 * It should be skipped by the loader
 */
class NotASchema
{
    public function getName(): string
    {
        return 'notASchema';
    }
}