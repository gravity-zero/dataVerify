<?php

namespace Gravity\Schema;

use Gravity\Context\ValidationContext;
use Gravity\Handlers\{FieldHandler, SubFieldHandler};
use Gravity\Engine\{ConditionalEngine, DataTraverser};
use Gravity\Exceptions\{ValidationTestNotFoundException, NoActiveFieldException};
use Gravity\Registry\{LazyValidationRegistry, RuleSetRegistry, SchemaRegistry, GlobalStrategyRegistry};
use Gravity\Documentation\IdeHelperManager;

/**
 * Fluent builder for creating schemas
 */
class SchemaBuilder
{
    private Schema $schema;
    private ValidationContext $context;
    private ConditionalEngine $conditionalEngine;
    private LazyValidationRegistry $lazyRegistry;

    public function __construct(string $name)
    {
        $this->schema = new Schema($name);
        $this->context = new ValidationContext();
        $this->lazyRegistry = LazyValidationRegistry::instance();
        
        $this->conditionalEngine = new ConditionalEngine(new DataTraverser([]));
        
        SchemaRegistry::instance()->register($this->schema);
        IdeHelperManager::instance()->notifySchemaRegistered();
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
     * Define a field
     */
    public function field(string $name): self
    {
        if ($this->conditionalEngine->isThenMode()) {
            $this->conditionalEngine->finalizeBlock();
        }

        $field = new FieldHandler($name);
        $this->context->push($field);
        $this->schema->addField($field);

        return $this;
    }

    /**
     * Define a subfield
     */
    public function subfield(string ...$path): self
    {
        if ($this->conditionalEngine->isThenMode()) {
            $this->conditionalEngine->finalizeBlock();
        }

        $field = $this->context->lastField();

        if (!$field) {
            throw new NoActiveFieldException("subfield");
        }

        if (!($field instanceof FieldHandler)) {
            throw new \LogicException("Cannot add subfield to non-FieldHandler");
        }

        $subField = new SubFieldHandler($path);
        $field->addSubField($subField);
        $this->context->push($subField);

        return $this;
    }

    /**
     * Start a conditional validation chain
     */
    public function when(string $field, string $operator, mixed $value): self
    {
        $this->conditionalEngine->when($field, $operator, $value);
        return $this;
    }

    /**
     * Add an AND condition
     */
    public function and(string $field, string $operator, mixed $value): self
    {
        $this->conditionalEngine->and($field, $operator, $value);
        return $this;
    }

    /**
     * Add an OR condition
     */
    public function or(string $field, string $operator, mixed $value): self
    {
        $this->conditionalEngine->or($field, $operator, $value);
        return $this;
    }

    /**
     * Apply a registered rule to the current field/subfield
     */
    public function rule(string $ruleName): self
    {
        $handler = $this->context->current();
        if (!$handler) {
            throw new NoActiveFieldException('rule');
        }

        $ruleSet = RuleSetRegistry::instance()->get($ruleName);
        if ($ruleSet === null) {
            throw new \LogicException("Rule '{$ruleName}' not found");
        }

        $conditions = null;
        if ($this->conditionalEngine->isThenMode()) {
            $conditions = $this->conditionalEngine->getCurrentConditions();
        }

        foreach ($ruleSet->getValidations() as $validation) {
            $handler->addValidation($validation['name'], $validation['args'], $conditions);
        }

        return $this;
    }

    /**
     * Magic property to access 'then'
     */
    public function __get(string $method)
    {
        if ($method === 'then') {
            $this->conditionalEngine->activateThenMode();
            return $this;
        }

        if (RuleSetRegistry::instance()->has($method)) {
            return $this->rule($method);
        }

        return $this->__call($method, []);
    }

    /**
     * Magic method to handle validations and rules
     */
    public function __call(string $method, array $args)
    {
        if ($method === 'then') {
            $this->conditionalEngine->activateThenMode();
            return $this;
        }

        $handler = $this->context->current();
        if (!$handler) {
            throw new NoActiveFieldException($method);
        }

        if ($this->conditionalEngine->hasPendingConditions() && !$this->conditionalEngine->isThenMode()) {
            throw new \LogicException(
                "Incomplete conditional validation. Use 'then' after 'when()'"
            );
        }

        if (empty($args) && RuleSetRegistry::instance()->has($method)) {
            return $this->rule($method);
        }

        if (!$this->hasValidation($method)) {
            throw new ValidationTestNotFoundException($method);
        }

        $conditions = null;
        if ($this->conditionalEngine->isThenMode()) {
            $conditions = $this->conditionalEngine->getCurrentConditions();
        }

        $handler->addValidation($method, $args, $conditions);

        return $this;
    }

    /**
     * Get the built schema (for testing)
     */
    public function getSchema(): Schema
    {
        return $this->schema;
    }
}