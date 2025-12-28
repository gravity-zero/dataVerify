<?php

namespace Gravity\Registry;

use Gravity\Interfaces\ValidationStrategyInterface;

/**
 * Lazy validation registry with automatic discovery
 * 
 * Automatically discovers validation classes from filesystem on-demand.
 * No need for generated mapping file - uses PSR-4 autoloading + conventions.
 */
class LazyValidationRegistry
{
    private static ?self $instance = null;
    
    /**
     * Loaded validation metadata cache
     * @var array<string, ValidationMetadata>
     */
    private array $loadedMetadata = [];
    
    /**
     * Cache of validation name → class name mapping (discovered on-demand)
     * @var array<string, class-string<ValidationStrategyInterface>>
     */
    private array $discoveredClasses = [];
    
    /**
     * Base namespace for validations
     */
    private const VALIDATION_NAMESPACE = 'Gravity\\Validations\\';
    
    /**
     * Validation directories to scan (in order of priority)
     */
    private const VALIDATION_DIRS = [
        'Core',
        'Type',
        'String',
        'Numeric',
        'Date',
        'File',
        'Comparison'
    ];
    
    /**
     * Get singleton instance
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Private constructor - preload core validations
     */
    private function __construct()
    {
        $this->preloadCore();
    }
    
    /**
     * Preload essential validations for performance
     */
    private function preloadCore(): void
    {
        $coreValidations = ['required', 'string', 'int', 'array', 'object'];
        
        foreach ($coreValidations as $name) {
            $this->get($name);
        }
    }
    
    /**
     * Get validation metadata, discovering and loading it lazily
     * 
     * @param string $name Validation name (e.g., 'email', 'minLength')
     * @return ValidationMetadata|null
     */
    public function get(string $name): ?ValidationMetadata
    {
        // Already loaded?
        if (isset($this->loadedMetadata[$name])) {
            return $this->loadedMetadata[$name];
        }
        
        // Try to discover the class
        $className = $this->discoverValidationClass($name);
        
        if ($className === null) {
            return null;
        }
        
        // Instantiate and discover metadata
        $instance = new $className();
        $metadata = ValidationDiscovery::discoverFromStrategy($instance);
        
        // Cache it
        $this->loadedMetadata[$name] = $metadata;
        
        return $metadata;
    }
    
    /**
     * Discover validation class name from validation name
     * 
     * Convention: 
     * - 'email' → EmailValidation in String/
     * - 'minLength' → MinLengthValidation in String/
     * - 'required' → RequiredValidation in Core/
     * 
     * @param string $name Validation name
     * @return class-string<ValidationStrategyInterface>|null
     */
    private function discoverValidationClass(string $name): ?string
    {
        // Already discovered?
        if (isset($this->discoveredClasses[$name])) {
            return $this->discoveredClasses[$name];
        }
        
        // Convert name to class name
        // 'minLength' → 'MinLength'
        // 'email' → 'Email'
        $className = ucfirst($name) . 'Validation';
        
        // Try each directory
        foreach (self::VALIDATION_DIRS as $dir) {
            $fullClassName = self::VALIDATION_NAMESPACE . $dir . '\\' . $className;
            
            if (class_exists($fullClassName)) {
                // Verify it implements the interface
                if (is_subclass_of($fullClassName, ValidationStrategyInterface::class)) {
                    $this->discoveredClasses[$name] = $fullClassName;
                    return $fullClassName;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Check if a validation exists
     */
    public function has(string $name): bool
    {
        return $this->discoverValidationClass($name) !== null;
    }
    
    /**
     * Get all available validation names by scanning directories
     * 
     * @return array<string>
     */
    public function getAvailableValidations(): array
    {
        $validations = [];
        $baseDir = dirname(__DIR__) . '/Validations';
        
        foreach (self::VALIDATION_DIRS as $dir) {
            $path = $baseDir . '/' . $dir;
            
            if (!is_dir($path)) {
                continue;
            }
            
            $files = glob($path . '/*Validation.php');
            
            foreach ($files as $file) {
                $className = basename($file, '.php');
                $fullClassName = self::VALIDATION_NAMESPACE . $dir . '\\' . $className;
                
                if (!class_exists($fullClassName)) {
                    continue;
                }
                
                // Instantiate to get the name
                try {
                    $instance = new $fullClassName();
                    if ($instance instanceof ValidationStrategyInterface) {
                        $validations[] = $instance->getName();
                    }
                } catch (\Throwable $e) {
                    // Skip invalid classes
                    continue;
                }
            }
        }
        
        return $validations;
    }
    
    /**
     * Get all loaded metadata
     * 
     * @return array<string, ValidationMetadata>
     */
    public function getAllLoadedMetadata(): array
    {
        return $this->loadedMetadata;
    }
    
    /**
     * Force load all validations (for documentation generation)
     * 
     * @return array<string, ValidationMetadata>
     */
    public function loadAll(): array
    {
        $allValidations = $this->getAvailableValidations();
        
        foreach ($allValidations as $name) {
            $this->get($name);
        }
        
        return $this->loadedMetadata;
    }
    
    /**
     * Get validation class name for a given validation name
     * 
     * @return class-string<ValidationStrategyInterface>|null
     */
    public function getValidationClass(string $name): ?string
    {
        return $this->discoverValidationClass($name);
    }
    
    /**
     * Clear loaded metadata cache
     */
    public function clearCache(): void
    {
        $this->loadedMetadata = [];
        $this->discoveredClasses = [];
    }
    
    /**
     * Reset singleton instance
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
