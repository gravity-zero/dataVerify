<?php

namespace Gravity\Exceptions;

class ValidationTestNotFoundException extends \Exception
{
    public function __construct(string $testName = '', int $code = 0, ?\Throwable $previous = null)
    {
        $message = $testName
            ? "Validation test '$testName' not found."
            : "Validation test not found.";

        parent::__construct($message, $code, $previous);
    }
}