<?php

namespace Tests\Fixtures\Schemas;

use Gravity\Interfaces\SchemaConfigInterface;
use Gravity\Schema\SchemaBuilder;

class ProductSchema implements SchemaConfigInterface
{
    public function getName(): string
    {
        return 'fixtureProduct';
    }

    public function define(SchemaBuilder $builder): void
    {
        $builder
            ->field('title')->required->string->minLength(3)
            ->field('price')->required->numeric
            ->field('stock')->int;
    }
}