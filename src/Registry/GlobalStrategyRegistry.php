<?php

namespace Gravity\Registry;

use Gravity\Interfaces\ValidationStrategyInterface;

/**
 * Global registry for validation strategies shared across all DataVerify instances
 */
class GlobalStrategyRegistry
{
    private static ?self $instance = null;
    private array $strategies = [];
    private array $metadataCache = [];
    
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
     * Private constructor for singleton
     */
    private function __construct() {}
    
    /**
     * Register a single strategy
     */
    public function register(ValidationStrategyInterface $strategy): self
    {
        $metadata = ValidationDiscovery::discoverFromStrategy($strategy);
        
        $this->strategies[$metadata->name] = $strategy;
        $this->metadataCache[$metadata->name] = $metadata;
        
        return $this;
    }
    
    /**
     * Register multiple strategies at once
     */
    public function registerMultiple(array $strategies): self
    {
        foreach ($strategies as $strategy) {
            if (!$strategy instanceof ValidationStrategyInterface) {
                throw new \InvalidArgumentException(
                    'All strategies must implement ValidationStrategyInterface'
                );
            }
            
            $this->register($strategy);
        }
        
        return $this;
    }
    
    /**
     * Load strategies from a directory
     * 
     * @param string $path Directory path
     * @param string $namespace Base namespace for strategy classes
     */
    public function loadFromDirectory(
        string $path,
        string $namespace
    ): self {
        if (!is_dir($path)) {
            throw new \InvalidArgumentException("Directory not found: {$path}");
        }
        
        $files = glob(rtrim($path, '/') . '/*.php');
        
        if ($files === false) {
            throw new \RuntimeException("Failed to read directory: {$path}");
        }
        
        foreach ($files as $file) {
            $className = basename($file, '.php');
            $fullClassName = rtrim($namespace, '\\') . '\\' . $className;
            
            if (!class_exists($fullClassName)) {
                require_once $file;
            }
            
            if (!class_exists($fullClassName)) {
                continue;
            }
            
            $reflection = new \ReflectionClass($fullClassName);
            
            if ($reflection->isAbstract() || $reflection->isInterface()) {
                continue;
            }
            
            if (!$reflection->implementsInterface(ValidationStrategyInterface::class)) {
                continue;
            }
            
            $strategy = $reflection->newInstance();
            $this->register($strategy);
        }
        
        return $this;
    }
    
    /**
     * Get all registered strategies
     * 
     * @return array<string, ValidationStrategyInterface>
     */
    public function getAll(): array
    {
        return $this->strategies;
    }
    
    /**
     * Get all cached metadata
     * 
     * @return array<string, ValidationMetadata>
     */
    public function getAllMetadata(): array
    {
        return $this->metadataCache;
    }
    
    /**
     * Check if a strategy is registered
     */
    public function has(string $name): bool
    {
        return isset($this->strategies[$name]);
    }
    
    /**
     * Get a specific strategy
     */
    public function get(string $name): ?ValidationStrategyInterface
    {
        return $this->strategies[$name] ?? null;
    }
    
    /**
     * Get count of registered strategies
     */
    public function count(): int
    {
        return count($this->strategies);
    }
    
    /**
     * Clear all registered strategies (useful for testing)
     */
    public function clear(): self
    {
        $this->strategies = [];
        $this->metadataCache = [];
        
        return $this;
    }
    
    /**
     * Reset singleton instance (useful for testing)
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}