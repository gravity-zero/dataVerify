<?php

namespace Gravity\Proxy;

use Gravity\DataVerify;
use Gravity\Registry\RuleSetRegistry;
use Gravity\Interfaces\ValidationHandlerInterface;

/**
 * Proxy for applying rules with multiple syntaxes
 * 
 * Supports:
 * - $dv->field("x")->rule("ruleName")
 * - $dv->field("x")->rule->ruleName
 * - $dv->field("x")->rule->ruleName()
 */
class RuleProxy
{
    private DataVerify $dataVerify;
    private ValidationHandlerInterface $handler;

    public function __construct(DataVerify $dataVerify, ValidationHandlerInterface $handler)
    {
        $this->dataVerify = $dataVerify;
        $this->handler = $handler;
    }

    /**
     * Apply a rule via property access
     * 
     * Usage: ->rule->strongPassword
     */
    public function __get(string $ruleName): DataVerify
    {
        return $this->applyRule($ruleName);
    }

    /**
     * Apply a rule via method call
     * 
     * Usage: ->rule->strongPassword()
     */
    public function __call(string $ruleName, array $args): DataVerify
    {
        return $this->applyRule($ruleName);
    }

    /**
     * Apply a rule to the current handler
     */
    private function applyRule(string $ruleName): DataVerify
    {
        $ruleSet = RuleSetRegistry::instance()->get($ruleName);
        
        if ($ruleSet === null) {
            throw new \LogicException("Rule '{$ruleName}' not found");
        }

        // Apply all validations from the rule
        foreach ($ruleSet->getValidations() as $validation) {
            $this->handler->addValidation($validation['name'], $validation['args']);
        }

        return $this->dataVerify;
    }
}
