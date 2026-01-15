<?php

namespace Gravity\Registry;

use Gravity\Schema\Schema;
use Gravity\Schema\SchemaBuilder;
use Gravity\Interfaces\SchemaConfigInterface;
use Gravity\Documentation\IdeHelperManager;

/**
 * Global registry for validation schemas
 */
class SchemaRegistry
{
    private static ?self $instance = null;
    /** @var array<string, Schema> */
    private array $schemas = [];

    private function __construct() {}

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register a new schema
     * 
     * @throws \LogicException If schema already exists
     */
    public function register(Schema $schema): self
    {
        $name = $schema->getName();
        
        if (isset($this->schemas[$name])) {
            throw new \LogicException("Schema '{$name}' is already registered");
        }
        
        $this->schemas[$name] = $schema;
        return $this;
    }

    /**
     * Get a registered schema
     */
    public function get(string $name): ?Schema
    {
        return $this->schemas[$name] ?? null;
    }

    /**
     * Check if a schema exists
     */
    public function has(string $name): bool
    {
        return isset($this->schemas[$name]);
    }

    /**
     * Get all registered schemas
     * 
     * @return array<string, Schema>
     */
    public function getAll(): array
    {
        return $this->schemas;
    }

    /**
     * Load schemas from a directory of PHP classes
     * 
     * Each class should implement SchemaConfigInterface:
     * ```php
     * class UserSchema implements SchemaConfigInterface {
     *     public function getName(): string { return 'user'; }
     *     public function define(SchemaBuilder $builder): void {
     *         $builder->field('email')->required->email;
     *     }
     * }
     * ```
     * 
     * @param string $path Directory containing schema classes
     * @param string $namespace Base namespace for the classes
     * @return array<string> List of loaded schema names
     * @throws \InvalidArgumentException If directory not found
     */
    public function loadFromDirectory(string $path, string $namespace): array
    {
        if (!is_dir($path)) {
            throw new \InvalidArgumentException("Directory not found: {$path}");
        }

        $files = glob(rtrim($path, '/') . '/*.php');
        
        if ($files === false) {
            throw new \RuntimeException("Failed to read directory: {$path}");
        }

        $loaded = [];

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

            if (!$reflection->implementsInterface(SchemaConfigInterface::class)) {
                continue;
            }

            /** @var SchemaConfigInterface $instance */
            $instance = $reflection->newInstance();
            $schemaName = $this->registerConfig($instance);
            
            if ($schemaName !== null) {
                $loaded[] = $schemaName;
            }
        }

        if (!empty($loaded)) {
            IdeHelperManager::instance()->notifySchemaRegistered();
        }

        return $loaded;
    }

    /**
     * Register a schema from a config class
     * 
     * @param SchemaConfigInterface $config The schema configuration
     * @return string|null The schema name or null if already registered
     */
    public function registerConfig(SchemaConfigInterface $config): ?string
    {
        $name = $config->getName();
        
        if ($this->has($name)) {
            return null;
        }

        $builder = new SchemaBuilder($name);
        $config->define($builder);

        return $name;
    }

    /**
     * Register multiple schema configs at once
     * 
     * @param array<SchemaConfigInterface> $configs
     * @return array<string> List of registered schema names
     */
    public function registerConfigs(array $configs): array
    {
        $loaded = [];

        foreach ($configs as $config) {
            if (!$config instanceof SchemaConfigInterface) {
                throw new \InvalidArgumentException(
                    'All configs must implement SchemaConfigInterface'
                );
            }

            $name = $this->registerConfig($config);
            if ($name !== null) {
                $loaded[] = $name;
            }
        }

        if (!empty($loaded)) {
            IdeHelperManager::instance()->notifySchemaRegistered();
        }

        return $loaded;
    }

    /**
     * Clear all schemas (for testing)
     */
    public function clear(): self
    {
        $this->schemas = [];
        return $this;
    }

    /**
     * Reset singleton (for testing)
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}