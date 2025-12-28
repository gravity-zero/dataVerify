<?php

namespace Gravity;

use Gravity\Context\ValidationContext;
use Gravity\Engine\{ValidationOrchestrator, ConditionalEngine, ErrorManager, DataTraverser};
use Gravity\Exceptions\{ValidationTestNotFoundException, NoActiveFieldException};
use Gravity\Collections\{FieldCollection, ErrorCollection};
use Gravity\Handlers\{FieldHandler, SubFieldHandler};
use Gravity\Interfaces\{DataVerifyInterface, ValidationStrategyInterface, TranslatorInterface};
use Gravity\Translation\TranslationManager;
use Gravity\Registry\{GlobalStrategyRegistry, ValidationRegistry, LazyValidationRegistry};

/**
 * Class DataVerify
 *
 * Main validation class using lazy-loaded validation strategies
 *
 * @property DataVerify $alphanumeric Validates that a value contains only alphanumeric characters
 * @property DataVerify $array Validates that a value is an array
 * @property DataVerify $boolean Validates that a value is a boolean. In strict mode (default), only true/false are accepted
 * @property DataVerify $containsLower Validates that a string contains at least one lowercase letter
 * @property DataVerify $containsNumber Validates that a string contains at least one digit
 * @property DataVerify $containsSpecialCharacter Validates that a string contains at least one special character
 * @property DataVerify $containsUpper Validates that a string contains at least one uppercase letter
 * @property DataVerify $date Validates that a value is a valid date in the specified format. Performs strict validation
 * @property DataVerify $disposableEmail Validates that an email is not from a disposable domain
 * @property DataVerify $email Validates that a value is a valid email address
 * @property DataVerify $fileExists Validates that a file exists at the given path
 * @property DataVerify $int Validates that a value is an integer. In strict mode (default), only true integers are accepted
 * @property DataVerify $ipAddress Validates that a value is a valid IP address (IPv4 or IPv6)
 * @property DataVerify $json Validates that a string is valid JSON
 * @property DataVerify $notAlphanumeric Validates that a value contains non-alphanumeric characters
 * @property DataVerify $numeric Validates that a value is numeric
 * @property DataVerify $object Validates that a value is an object
 * @property DataVerify $required Validates that a field is not empty. Objects are cast to arrays to check emptiness.
 * @property DataVerify $string Validates that a value is a string
 * @property DataVerify $then Activate conditional validation mode
 *
 * @method DataVerify between(DateTime|string|int|float $min, DateTime|string|int|float $max) Validates that a value is between two bounds. Supports numeric values and DateTime objects
 * @method DataVerify boolean(bool $strict = true) Validates that a value is a boolean. In strict mode (default), only true/false are accepted
 * @method DataVerify date(string $format = 'Y-m-d') Validates that a value is a valid date in the specified format. Performs strict validation
 * @method DataVerify disposableEmail(array $disposables = []) Validates that an email is not from a disposable domain
 * @method DataVerify fileMime(array|string $mime) Validates that a file has an allowed MIME type
 * @method DataVerify greaterThan(mixed $min) Validates that a value is greater than a specified limit
 * @method DataVerify in(object|array $allowed) Validates that a value exists in an allowed list or as an object property
 * @method DataVerify int(bool $strict = true) Validates that a value is an integer. In strict mode (default), only true integers are accepted
 * @method DataVerify lowerThan(mixed $max) Validates that a value is less than a specified limit
 * @method DataVerify maxLength(int $max) Validates that a string does not exceed maximum length
 * @method DataVerify minLength(int $min) Validates that a string has a minimum length
 * @method DataVerify notIn(object|array $forbidden) Validates that a value does not exist in a forbidden list or as an object property
 * @method DataVerify regex(string $pattern) Validates that a value matches a regular expression pattern. Warnings are suppressed
 */
class DataVerify implements DataVerifyInterface
{
    private array|object $data;
    private ValidationContext $context;
    private FieldCollection $fields;
    private ErrorCollection $errors;
    private TranslationManager $translationManager;
    private bool $hasVerified = false;

    // Engines
    private ValidationOrchestrator $orchestrator;
    private ConditionalEngine $conditionalEngine;
    private ErrorManager $errorManager;
    private DataTraverser $dataTraverser;
    private LazyValidationRegistry $lazyRegistry;

    public function __construct(array|object $data)
    {
        $this->data = $data;
        $this->context = new ValidationContext();
        $this->fields = new FieldCollection();
        $this->errors = new ErrorCollection();
        $this->translationManager = new TranslationManager();
        $this->lazyRegistry = LazyValidationRegistry::instance();
        
        $this->initializeEngines();
    }

    private function initializeEngines(): void
    {
        $this->dataTraverser = new DataTraverser($this->data);
        $this->conditionalEngine = new ConditionalEngine($this->dataTraverser);
        $this->errorManager = new ErrorManager($this->errors, $this->translationManager, $this->lazyRegistry);
        
        $this->orchestrator = new ValidationOrchestrator(
            $this->fields,
            $this->errors,
            $this->errorManager,
            $this->dataTraverser,
            $this->conditionalEngine,
            new ValidationRegistry(),
            $this->lazyRegistry
        );
    }

    public function field(string $name): self
    {
        $field = new FieldHandler($name);
        $this->context->push($field);
        $this->fields->add($field);
        
        if (!$this->fields->hasField($name)) {
            throw new \LogicException("Field {$name} was not added to collection");
        }
        
        return $this;
    }

    public function subfield(string ...$path): self
    {
        $field = $this->context->lastField();

        if(!$field){
            throw new NoActiveFieldException("subfield");
        }

        if (!($field instanceof FieldHandler)) {
            throw new \LogicException("Cannot add subfield to non-FieldHandler");
        }
        
        $subField = new SubFieldHandler($path);
        $field->addSubField($subField);
        
        $previousContext = $this->context->current();
        
        $this->context->push($subField);
        
        if ($this->context->current() === $previousContext) {
            throw new \LogicException("Context did not change after push");
        }
        
        if (!$field->getSubFields()->hasSubField($path)) {
            throw new \LogicException("SubField was not added to parent");
        }
        
        return $this;
    }

    public function when(string $field, string $operator, mixed $value): self
    {
        $this->conditionalEngine->when($field, $operator, $value);
        return $this;
    }

    public function and(string $field, string $operator, mixed $value): self
    {
        $this->conditionalEngine->and($field, $operator, $value);
        return $this;
    }

    public function or(string $field, string $operator, mixed $value): self
    {
        $this->conditionalEngine->or($field, $operator, $value);
        return $this;
    }

    public function __get(string $method)
    {
        if ($method === 'then') {
            $this->conditionalEngine->activateThenMode();
            return $this;
        }

        return $this->__call($method, []);
    }

    public function __call(string $method, array $args)
    {
        $handler = $this->context->current();

        if (!$handler) {
            throw new NoActiveFieldException($method);
        }

        if ($this->conditionalEngine->hasPendingConditions() && !$this->conditionalEngine->isThenMode()) {
            throw new \LogicException(
                "Incomplete conditional validation. Use 'then' after 'when()'"
            );
        }

        if (!$this->orchestrator->hasStrategy($method)) {
            throw new ValidationTestNotFoundException($method);
        }

        if ($this->conditionalEngine->isThenMode() && $this->conditionalEngine->hasPendingConditions()) {
            $conditionMet = $this->conditionalEngine->evaluateConditions();
            
            if ($conditionMet) {
                $handler->addValidation($method, $args);
            }
            
            $this->conditionalEngine->reset();
            
            return $this;
        }

        $handler->addValidation($method, $args);
        
        $validations = $handler->getValidations();
        if (!array_key_exists($method, $validations)) {
            throw new \LogicException("Validation {$method} was not registered");
        }
        
        return $this;
    }

    public function alias(string $name): self
    {
        $handler = $this->context->current();
        if (!$handler) {
            throw new NoActiveFieldException('alias');
        }

        $handler->setAlias($name);

        return $this;
    }

    public function setTranslator(TranslatorInterface $translator): self
    {
        $this->translationManager->setTranslator($translator);
        return $this;
    }

    public function setLocale(string $locale): self
    {
        $this->translationManager->setLocale($locale);
        return $this;
    }

    public function addTranslations(array $translations, string $locale = 'en'): self
    {
        $this->translationManager->addTranslations($translations, $locale);
        return $this;
    }

    public function loadLocale(string $locale, ?string $filePath = null): self
    {
        $this->translationManager->loadLocale($locale, $filePath);
        return $this;
    }

    public function errorMessage(string $message): self
    {
        $handler = $this->context->current();
        if (!$handler) {
            throw new NoActiveFieldException("error_message");
        }

        $handler->setErrorMessage($message);

        return $this;
    }

    public function verify(bool $batch = true): bool
    {
        if ($this->hasVerified) {
            throw new \LogicException(
                'DataVerify instance has already been verified. Create a new instance for each validation.'
            );
        }
        
        $this->hasVerified = true;
        
        return $this->orchestrator->verify($batch);
    }

    /**
     * Access global strategy registry
     */
    public static function global(): GlobalStrategyRegistry
    {
        return GlobalStrategyRegistry::instance();
    }

    public function registerStrategy(ValidationStrategyInterface $strategy): self
    {
        $this->orchestrator->registerStrategy($strategy);

        \Gravity\Documentation\IdeHelperManager::instance()->registerStrategy($strategy);
        
        return $this;
    }

    public function getErrors(bool $asObject = false): array
    {
        return $asObject ? $this->errors->toObjects() : $this->errors->toArray();
    }

    /**
     * Generate documentation for all registered validations
     */
    public function generateDocumentation(string $format = 'markdown'): string
    {
        return match($format) {
            'json' => \Gravity\Documentation\DocumentationGenerator::generateJson($this->orchestrator->getRegistry()),
            'list' => \Gravity\Documentation\DocumentationGenerator::generateList($this->orchestrator->getRegistry()),
            default => \Gravity\Documentation\DocumentationGenerator::generate($this->orchestrator->getRegistry())
        };
    }

    /**
     * List all available validations
     */
    public function listValidations(?string $category = null): array
    {
        $registry = $this->orchestrator->getRegistry();
        return $category !== null 
            ? $registry->getByCategory($category)
            : $registry->getAll();
    }

    /**
     * Get metadata for a specific validation
     */
    public function getValidationMetadata(string $name): ?\Gravity\Registry\ValidationMetadata
    {
        return $this->orchestrator->getRegistry()->getMetadata($name);
    }

    /**
     * Get all validation categories
     */
    public function getValidationCategories(): array
    {
        return $this->orchestrator->getRegistry()->getCategories();
    }

    /**
     * Generate OpenAPI schema
     */
    public function generateOpenApiSchema(array $options = []): string
    {
        return \Gravity\Documentation\OpenApiGenerator::generate(
            $this->orchestrator->getRegistry(),
            $options
        );
    }

    /**
     * Generate JSON Schema
     */
    public function generateJsonSchema(): string
    {
        return \Gravity\Documentation\OpenApiGenerator::generateJsonSchema(
            $this->orchestrator->getRegistry()
        );
    }

    /**
     * Generate Swagger UI HTML
     */
    public function generateSwaggerUI(array $options = []): string
    {
        return \Gravity\Documentation\OpenApiGenerator::generateSwaggerUI(
            $this->orchestrator->getRegistry(),
            $options
        );
    }

    /**
     * Enable automatic IDE helper generation for custom strategies (development only)
     * 
     * When enabled, a .ide-helper.php file is automatically generated whenever
     * a custom validation strategy is registered, providing IDE autocompletion.
     * 
     * Usage:
     * ```php
     * // In bootstrap or service provider (development only)
     * if (getenv('APP_ENV') === 'development') {
     *     DataVerify::enableIdeHelper();
     * }
     * ```
     * 
     * @param string|null $outputPath Custom path for .ide-helper.php (default: ./.ide-helper.php)
     */
    public static function enableIdeHelper(?string $outputPath = null): void
    {
        \Gravity\Documentation\IdeHelperManager::instance()->enable($outputPath);
    }
}
