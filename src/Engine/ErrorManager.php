<?php

namespace Gravity\Engine;

use Gravity\Collections\ErrorCollection;
use Gravity\Translation\TranslationManager;
use Gravity\ValidationError;
use Gravity\Interfaces\ValidationHandlerInterface;
use Gravity\Registry\{ValidationMetadata, GlobalStrategyRegistry, LazyValidationRegistry};

/**
 * ErrorManager
 * 
 * Responsible for error creation, message translation, and parameter extraction.
 */
class ErrorManager
{
    public function __construct(
        private ErrorCollection $errors,
        private TranslationManager $translationManager,
        private LazyValidationRegistry $registry
    ) {}

    /**
     * Add validation error with translated message
     */
    public function addError(
        ValidationHandlerInterface $handler,
        string $testName,
        mixed $value,
        string $path,
        array $args = []
    ): void {
        $alias = $handler->getAlias() ?? $path;
        $errorMessage = $handler->getErrorMessage();

        if (!$errorMessage) {
            $params = $this->buildValidationParams($testName, $args);
            $errorMessage = $this->translationManager->getValidationMessage(
                $testName,
                $alias,
                $value,
                $params
            );
        }
        
        $this->errors->add(
            new ValidationError($path, $alias, $testName, $errorMessage, $value)
        );
    }

    /**
     * Build parameters from args for error messages
     */
    private function buildValidationParams(string $testName, array $args): array
    {
        $metadata = $this->resolveMetadata($testName);
        if (!$metadata) {
            return [];
        }

        return $this->mapArgsToParams($metadata, $args);
    }


    private function resolveMetadata(string $testName): ?ValidationMetadata
    {
        return $this->registry->get($testName)
            ?? GlobalStrategyRegistry::instance()->getAllMetadata()[$testName]
            ?? null;
    }

    private function mapArgsToParams(ValidationMetadata $metadata, array $args): array
    {
        if (empty($metadata->parameters)) {
            return [];
        }

        $params = [];
        foreach ($metadata->parameters as $i => $p) {
            $name = $p['name'];
            $value = $args[$i] ?? ($p['default'] ?? null);

            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            $params[$name] = $value ?? '';
        }

        return $params;
    }

    /**
     * Get translation manager for external configuration
     */
    public function getTranslationManager(): TranslationManager
    {
        return $this->translationManager;
    }
}