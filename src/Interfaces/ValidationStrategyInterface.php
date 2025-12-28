<?php

namespace Gravity\Interfaces;

interface ValidationStrategyInterface
{
    /**
     * Internal execution adapter - DO NOT OVERRIDE
     * 
     * This method is called internally by DataVerify and should not be modified.
     * Implement your validation logic in handler() instead with named parameters
     * for automatic translation placeholder support.
     * 
     * @internal
     */
    public function execute(mixed $value, array $args): bool;

    /**
     * Get the name of the validation rule
     *
     * @return string The rule name (used in ->field('x')->ruleName())
     */
    public function getName(): string;
}