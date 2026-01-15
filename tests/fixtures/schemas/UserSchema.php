<?php

namespace Tests\Fixtures\Schemas;

use Gravity\Interfaces\SchemaConfigInterface;
use Gravity\Schema\SchemaBuilder;

class UserSchema implements SchemaConfigInterface
{
    public function getName(): string
    {
        return 'fixtureUser';
    }

    public function define(SchemaBuilder $builder): void
    {
        $builder
            ->field('email')->required->email
            ->field('name')->required->string->minLength(2)
            ->field('age')->numeric;
    }
}