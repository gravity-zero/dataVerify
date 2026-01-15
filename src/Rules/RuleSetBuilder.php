<?php

namespace Gravity\Rules;

use Gravity\Exceptions\ValidationTestNotFoundException;
use Gravity\Registry\{LazyValidationRegistry, RuleSetRegistry, GlobalStrategyRegistry};
use Gravity\Documentation\IdeHelperManager;

/**
 * Fluent builder for creating rule sets
 */
class RuleSetBuilder
{
    private RuleSet $ruleSet;
    private LazyValidationRegistry $lazyRegistry;

    public function __construct(string $name)
    {
        $this->ruleSet = new RuleSet($name);
        $this->lazyRegistry = LazyValidationRegistry::instance();
        
        RuleSetRegistry::instance()->register($this->ruleSet);
        IdeHelperManager::instance()->notifyRuleRegistered();
    }

    /**
     * Check if a validation exists in any registry
     */
    private function hasValidation(string $name): bool
    {
        return $this->lazyRegistry->has($name) 
            || GlobalStrategyRegistry::instance()->has($name);
    }

    /**
     * Magic method to handle validation rules
     */
    public function __call(string $method, array $args): self
    {
        if (!$this->hasValidation($method)) {
            throw new ValidationTestNotFoundException($method);
        }

        $this->ruleSet->addValidation($method, $args);
        
        return $this;
    }

    /**
     * Magic property to handle validation rules without parameters
     */
    public function __get(string $method): self
    {
        return $this->__call($method, []);
    }

    /**
     * Get the built rule set (for testing)
     */
    public function getRuleSet(): RuleSet
    {
        return $this->ruleSet;
    }
}