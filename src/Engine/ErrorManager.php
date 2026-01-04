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
 * Optimized with metadata caching to avoid repeated lookups.
 */
class ErrorManager
{
    private ?TranslationManager $translationManager = null;
    
    /**
     * Cache for resolved metadata (validation name → metadata)
     * Avoids repeated registry lookups for the same validation
     * @var array<string, ValidationMetadata|null>
     */
    private array $metadataCache = [];
    
    public function __construct(
        private ErrorCollection $errors,
        private LazyValidationRegistry $registry,
        ?TranslationManager $translationManager = null
    ) {
        $this->translationManager = $translationManager;
    }

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
            $errorMessage = $this->getTranslationManager()->getValidationMessage(
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
     * Optimized with metadata caching
     */
    private function buildValidationParams(string $testName, array $args): array
    {
        if (empty($args)) {
            return [];
        }
        
        $metadata = $this->resolveMetadata($testName);
        if (!$metadata || empty($metadata->parameters)) {
            return [];
        }

        return $this->mapArgsToParams($metadata, $args);
    }

    /**
     * Resolve metadata with caching
     */
    private function resolveMetadata(string $testName): ?ValidationMetadata
    {
        if (array_key_exists($testName, $this->metadataCache)) {
            return $this->metadataCache[$testName];
        }
        
        $metadata = $this->registry->get($testName)
            ?? GlobalStrategyRegistry::instance()->getAllMetadata()[$testName]
            ?? null;
        
        $this->metadataCache[$testName] = $metadata;
        
        return $metadata;
    }

    /**
     * Map args to parameter names
     * ✅ Optimisation : early return si pas de parameters
     */
    private function mapArgsToParams(ValidationMetadata $metadata, array $args): array
    {
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
     * Lazy-loads if not already set
     */
    public function getTranslationManager(): TranslationManager
    {
        if ($this->translationManager === null) {
            $this->translationManager = new TranslationManager();
        }
        return $this->translationManager;
    }
    
    /**
     * Set translation manager (for user customization)
     */
    public function setTranslationManager(TranslationManager $translationManager): void
    {
        $this->translationManager = $translationManager;
    }
}