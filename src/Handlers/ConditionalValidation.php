<?php

namespace Gravity\Handlers;

class ConditionalValidation
{
    public function __construct(
        public readonly string $field,
        public readonly string $operator,
        public readonly mixed $value,
        public readonly string $validation,
        public readonly array $args
    ) {}
}