<?php

namespace Gravity\Engine;

use Gravity\Interfaces\ValidationHandlerInterface;
use Gravity\Registry\RuleSetRegistry;

/**
 * Handles application of registered rule sets to field handlers
 * 
 * Note: Rules are field-agnostic and do NOT support conditional logic.
 * Conditions are only available in Schemas where rules are applied to specific fields.
 */
class RuleEngine
{
    public function __construct(
        private RuleSetRegistry $ruleSetRegistry
    ) {}

    /**
     * Apply a rule set to a handler
     * 
     * @throws \LogicException If rule set not found
     */
    public function apply(ValidationHandlerInterface $handler, string $ruleSetName): void
    {
        $ruleSet = $this->ruleSetRegistry->get($ruleSetName);

        if ($ruleSet === null) {
            throw new \LogicException("Rule '{$ruleSetName}' not found");
        }

        foreach ($ruleSet->getValidations() as $validation) {
            $handler->addValidation($validation['name'], $validation['args']);
        }
    }
}