<?php

namespace Gravity\Registry;

use Gravity\Rules\RuleSet;
use Gravity\Documentation\IdeHelperManager;

/**
 * Global registry for reusable validation rules
 */
class RuleSetRegistry
{
    private static ?self $instance = null;
    /** @var array<string, RuleSet> */
    private array $rules = [];

    private function __construct() {}

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register a new rule set
     * 
     * @throws \LogicException If rule already exists
     */
    public function register(RuleSet $ruleSet): self
    {
        $name = $ruleSet->getName();
        
        if (isset($this->rules[$name])) {
            throw new \LogicException("Rule '{$name}' is already registered");
        }
        
        $this->rules[$name] = $ruleSet;
        return $this;
    }

    /**
     * Get a registered rule set
     */
    public function get(string $name): ?RuleSet
    {
        return $this->rules[$name] ?? null;
    }

    /**
     * Check if a rule exists
     */
    public function has(string $name): bool
    {
        return isset($this->rules[$name]);
    }

    /**
     * Get all registered rules
     * 
     * @return array<string, RuleSet>
     */
    public function getAll(): array
    {
        return $this->rules;
    }

    /**
     * Load rules from a directory of PHP files
     * 
     * Each file should return a callable that registers the rule:
     * ```php
     * // config/rules/strongPassword.php
     * return fn() => DataVerify::registerRules('strongPassword')
     *     ->minLength(12)->containsUpper;
     * ```
     * 
     * @param string $path Directory containing rule files
     * @return array<string> List of loaded filenames
     * @throws \InvalidArgumentException If directory not found
     * @throws \RuntimeException If a file doesn't return a callable
     */
    public function loadFromDirectory(string $path): array
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
            $loaded[] = $this->loadFile($file);
        }

        if (!empty($loaded)) {
            IdeHelperManager::instance()->notifyRuleRegistered();
        }

        return $loaded;
    }

    /**
     * Load a single rule file
     * 
     * @param string $filePath Path to the rule file
     * @return string The filename (without extension)
     * @throws \InvalidArgumentException If file not found
     * @throws \RuntimeException If file doesn't return a callable
     */
    public function loadFile(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        $result = require $filePath;

        if (!is_callable($result)) {
            throw new \RuntimeException(
                "Rule file must return a callable: {$filePath}"
            );
        }

        $result();

        return pathinfo($filePath, PATHINFO_FILENAME);
    }

    /**
     * Clear all rules (for testing)
     */
    public function clear(): self
    {
        $this->rules = [];
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