<?php

namespace Gravity\Exceptions;

class NoActiveFieldException extends \LogicException
{
    public function __construct(string $operation)
    {
        parent::__construct(
            "Cannot call '{$operation}' without an active field or subfield. Call field() first."
        );
    }
}