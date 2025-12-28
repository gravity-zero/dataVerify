<?php

namespace Gravity\Engine;

use Gravity\Enums\ConditionOperator;
use Gravity\Enums\ConditionalOperator;

/**
 * ConditionalEngine
 * 
 * Responsible for conditional validation logic (when/and/or/then).
 * Manages condition chains and evaluates them against data.
 */
class ConditionalEngine
{
    private ?array $pendingConditions = null;
    private ConditionOperator $conditionOperator = ConditionOperator::AND;
    private bool $thenMode = false;

    public function __construct(
        private DataTraverser $dataTraverser
    ) {}

    /**
     * Start a new conditional chain
     */
    public function when(string $field, string $operator, mixed $value): void
    {
        $this->validateOperator($operator);
        
        if ($this->pendingConditions !== null && !$this->thenMode) {
            throw new \LogicException(
                "Previous 'when()' was not followed by 'then'. " .
                "Complete the conditional validation before starting a new one."
            );
        }
        
        $this->pendingConditions = [
            [
                'field' => $field,
                'operator' => $operator,
                'value' => $value
            ]
        ];
    }

    /**
     * Add AND condition to current chain
     */
    public function and(string $field, string $operator, mixed $value): void
    {
        $this->validateOperator($operator);
        
        if ($this->pendingConditions === null) {
            throw new \LogicException(
                "Cannot use 'and' without 'when()'. Start with when() first."
            );
        }
        
        if ($this->thenMode) {
            throw new \LogicException(
                "Cannot use 'and' after 'then'. Use 'and' before 'then'."
            );
        }
        
        if (count($this->pendingConditions) > 1 && $this->conditionOperator === ConditionOperator::OR) {
            throw new \LogicException(
                "Cannot mix 'and' with 'or'. Use parentheses or separate validations."
            );
        }
        
        $this->conditionOperator = ConditionOperator::AND;
        
        $this->pendingConditions[] = [
            'field' => $field,
            'operator' => $operator,
            'value' => $value
        ];
    }

    /**
     * Add OR condition to current chain
     */
    public function or(string $field, string $operator, mixed $value): void
    {
        $this->validateOperator($operator);
        
        if ($this->pendingConditions === null) {
            throw new \LogicException(
                "Cannot use 'or' without 'when()'. Start with when() first."
            );
        }
        
        if ($this->thenMode) {
            throw new \LogicException(
                "Cannot use 'or' after 'then'. Use 'or' before 'then'."
            );
        }
        
        if (count($this->pendingConditions) > 1 && $this->conditionOperator === ConditionOperator::AND) {
            throw new \LogicException(
                "Cannot mix 'or' with 'and'. Use parentheses or separate validations."
            );
        }
        
        $this->conditionOperator = ConditionOperator::OR;
        
        $this->pendingConditions[] = [
            'field' => $field,
            'operator' => $operator,
            'value' => $value
        ];
    }

    /**
     * Activate then mode
     */
    public function activateThenMode(): void
    {
        if (!$this->pendingConditions) {
            throw new \LogicException(
                "Cannot use 'then' without 'when'. Use when() before then."
            );
        }

        $this->thenMode = true;
    }

    /**
     * Check if currently in then mode
     */
    public function isThenMode(): bool
    {
        return $this->thenMode;
    }

    /**
     * Check if there are pending conditions
     */
    public function hasPendingConditions(): bool
    {
        return $this->pendingConditions !== null;
    }

    /**
     * Evaluate all pending conditions
     */
    public function evaluateConditions(): bool
    {
        if ($this->pendingConditions === null) {
            return false;
        }

        $results = [];
        
        foreach ($this->pendingConditions as $condition) {
            $field = $condition['field'];
            $operator = $condition['operator'];
            $expectedValue = $condition['value'];
            
            $actualValue = $this->dataTraverser->getFieldValue($field);
            
            $results[] = $this->evaluateSingleCondition($actualValue, $operator, $expectedValue);
        }
        
        if (count($results) === 1) {
            return $results[0];
        }
        
        return match($this->conditionOperator) {
            ConditionOperator::AND => !in_array(false, $results, true),
            ConditionOperator::OR => in_array(true, $results, true),
        };
    }

    /**
     * Validate that operator is recognized
     */
    private function validateOperator(string $operator): void
    {
        if (ConditionalOperator::tryFrom($operator) === null) {
            $validOperators = implode(', ', array_map(
                fn($case) => $case->value,
                ConditionalOperator::cases()
            ));
            
            throw new \InvalidArgumentException(
                "Invalid operator '{$operator}'. Allowed operators: {$validOperators}"
            );
        }
    }

    /**
     * Evaluate a single condition
     * 
     * Public for reuse by ValidationOrchestrator (conditional validations)
     */
    public function evaluateSingleCondition(mixed $actual, string $operator, mixed $expected): bool
    {
        $this->validateOperator($operator);
        
        return match($operator) {
            '=' => $actual === $expected,
            '!=' => $actual !== $expected,
            '>' => $actual > $expected,
            '>=' => $actual >= $expected,
            '<' => $actual < $expected,
            '<=' => $actual <= $expected,
            'in' => is_array($expected) && in_array($actual, $expected, true),
            'not_in' => is_array($expected) && !in_array($actual, $expected, true),
            default => throw new \InvalidArgumentException("Unknown operator: {$operator}")
        };
    }

    /**
     * Reset conditional state (called after validation or on error)
     */
    public function reset(): void
    {
        $this->pendingConditions = null;
        $this->thenMode = false;
        $this->conditionOperator = ConditionOperator::AND;
    }
}