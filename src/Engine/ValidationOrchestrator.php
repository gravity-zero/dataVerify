<?php

namespace Gravity\Engine;

use Gravity\Collections\{FieldCollection, ErrorCollection};
use Gravity\Handlers\{FieldHandler, SubFieldHandler};
use Gravity\Interfaces\{ValidationHandlerInterface, ValidationStrategyInterface};
use Gravity\Exceptions\StopValidationException;
use Gravity\Registry\{ValidationRegistry, ValidationDiscovery, ValidationMetadata, LazyValidationRegistry};
use Gravity\Registry\GlobalStrategyRegistry;

/**
 * ValidationOrchestrator
 * 
 * Responsible for orchestrating the validation process:
 * - Iterating through fields and subfields
 * - Executing validations (native methods + custom strategies)
 * - Managing batch vs fail-fast modes
 * 
 * Now uses LazyValidationRegistry for on-demand loading of native validations
 */
class ValidationOrchestrator
{
    public function __construct(
        private FieldCollection $fields,
        private ErrorCollection $errors,
        private ErrorManager $errorManager,
        private DataTraverser $dataTraverser,
        private ConditionalEngine $conditionalEngine,
        private ValidationRegistry $registry,
        private LazyValidationRegistry $lazyRegistry
    ) {
        // No upfront initialization needed - lazy loading handles it
    }

    /**
     * Execute all validations
     * 
     * @param bool $batch True for batch mode (collect all errors), false for fail-fast
     * @return bool True if validation passed, false otherwise
     */
    public function verify(bool $batch = true): bool
    {
        $batchMode = $batch;
        $this->errors->clear();
        
        try {
            foreach ($this->fields as $field) {
                $this->validateField($field, $batchMode);
            }
        } catch (StopValidationException $e) {
            // Expected stop for fail-fast mode
        } finally {
            $this->conditionalEngine->reset();
        }
        
        return $this->errors->isEmpty();
    }

    /**
     * Validate a single field and its subfields
     */
    private function validateField(FieldHandler $field, bool $batchMode): void
    {
        $value = $this->dataTraverser->getValue($field->getName());
        $this->processValidations($field, $value, $field->getName(), $batchMode);

        foreach ($field->getSubFields() as $subField) {
            $this->validateSubField($field, $subField, $value, $batchMode);
        }
    }

    /**
     * Validate a subfield (nested path)
     */
    private function validateSubField(
        FieldHandler $parent,
        SubFieldHandler $subField,
        mixed $parentValue,
        bool $batchMode
    ): void {
        $value = $this->dataTraverser->traversePath($parentValue, $subField->getPath());
        $fullPath = $parent->getName() . '.' . implode('.', $subField->getPath());

        $this->processValidations($subField, $value, $fullPath, $batchMode);
    }

    /**
     * Process all validations for a handler (field or subfield)
     */
    private function processValidations(
        ValidationHandlerInterface $handler,
        mixed $value,
        string $path,
        bool $batchMode
    ): void {
        // Regular validations
        foreach ($handler->getValidations() as $validation) {
            $this->executeValidation($handler, $validation['name'], $validation['args'], $value, $path);
            
            if ($this->shouldStopValidation($batchMode)) {
                $lastError = $this->errors->getLastError();
                if ($lastError && $lastError->getField() === $path) {
                    throw new StopValidationException();
                }
            }
        }

        // Conditional validations
        foreach ($handler->getConditionalValidations() as $conditional) {
            if ($this->shouldExecuteConditional($conditional)) {
                $this->executeValidation($handler, $conditional->validation, $conditional->args, $value, $path);
                
                if ($this->shouldStopValidation($batchMode)) {
                    $lastError = $this->errors->getLastError();
                    if ($lastError && $lastError->getField() === $path) {
                        throw new StopValidationException();
                    }
                }
            }
        }
    }

    /**
     * Execute a single validation test
     */
    private function executeValidation(
        ValidationHandlerInterface $handler,
        string $test,
        array $args,
        mixed $value,
        string $path
    ): void {
        // 'required' is special - always execute even on empty values
        if ($test === 'required' || !$this->dataTraverser->isValueEmpty($value)) {
            $result = $this->runValidationTest($test, $value, $args);
            if (!$result) {
                $this->errorManager->addError($handler, $test, $value, $path, $args);
            }
        }
    }

    /**
     * Run validation test - tries lazy registry, then instance registry, then global
     * 
     * Order of precedence:
     * 1. Lazy native validations (core library validations)
     * 2. Instance-specific strategies (registered via ->registerStrategy())
     * 3. Global strategies (registered via DataVerify::global()->register())
     */
    private function runValidationTest(string $testName, mixed $value, array $args): bool
    {
        // Try lazy registry first (native validations)
        $metadata = $this->lazyRegistry->get($testName);
        if ($metadata !== null) {
            return ($metadata->callable)($value, ...$args);
        }
        
        // Try instance registry (instance-specific strategies)
        if ($this->registry->has($testName)) {
            return $this->registry->execute($testName, $value, $args);
        }
        
        // Try global registry (global strategies)
        $globalMetadata = GlobalStrategyRegistry::instance()->getAllMetadata()[$testName] ?? null;
        if ($globalMetadata !== null) {
            return ($globalMetadata->callable)($value, ...$args);
        }
        
        throw new \InvalidArgumentException("Validation '{$testName}' not found in any registry");
    }

    /**
     * Check if conditional validation should execute
     * 
     * Delegates to ConditionalEngine for condition evaluation
     */
    private function shouldExecuteConditional($conditional): bool
    {
        $fieldValue = $this->dataTraverser->getFieldValue($conditional->field);
        
        return $this->conditionalEngine->evaluateSingleCondition(
            $fieldValue,
            $conditional->operator,
            $conditional->value
        );
    }

    /**
     * Check if validation should stop (fail-fast mode)
     */
    private function shouldStopValidation(bool $batchMode): bool
    {
        return !$batchMode && !$this->errors->isEmpty();
    }

    /**
     * Register a custom validation strategy (instance-specific)
     */
    public function registerStrategy(ValidationStrategyInterface $strategy): void
    {
        $metadata = ValidationDiscovery::discoverFromStrategy($strategy);
        $this->registry->register($metadata);
    }

    /**
     * Register metadata directly (used for global strategies)
     */
    public function registerMetadata(ValidationMetadata $metadata): void
    {
        $this->registry->register($metadata);
    }

    /**
     * Check if a validation is registered (any registry)
     */
    public function hasStrategy(string $name): bool
    {
        return $this->lazyRegistry->has($name)
            || $this->registry->has($name) 
            || GlobalStrategyRegistry::instance()->has($name);
    }

    /**
     * Get the validation registry (for documentation generation)
     * 
     * Note: This loads all lazy validations to ensure complete documentation
     */
    public function getRegistry(): ValidationRegistry
    {
        // Load all validations from lazy registry into instance registry
        // This ensures documentation generation has access to all validations
        $allMetadata = $this->lazyRegistry->loadAll();
        
        foreach ($allMetadata as $metadata) {
            if (!$this->registry->has($metadata->name)) {
                $this->registry->register($metadata);
            }
        }
        
        return $this->registry;
    }
}