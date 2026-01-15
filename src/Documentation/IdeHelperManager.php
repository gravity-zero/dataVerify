<?php

namespace Gravity\Documentation;

use Gravity\Interfaces\ValidationStrategyInterface;

/**
 * Manages automatic IDE helper generation for custom validation strategies, rules and schemas
 * 
 * This class handles the opt-in IDE autocompletion feature for development environments.
 * It generates .ide-helper.php files automatically when custom strategies, rules or schemas are registered.
 */
class IdeHelperManager
{
    private static ?self $instance = null;
    private bool $enabled = false;
    private string $outputPath = '';
    private array $registeredStrategies = [];
    private int $lastGenerated = 0;
    private bool $debounceEnabled = true;
    
    private function __construct()
    {
        $this->outputPath = getcwd() . '/.ide-helper.php';
    }
    
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
     * Disable debounce (for testing purposes)
     * 
     * @internal
     */
    public function disableDebounce(): void
    {
        $this->debounceEnabled = false;
    }
    
    /**
     * Enable debounce (default behavior)
     */
    public function enableDebounce(): void
    {
        $this->debounceEnabled = true;
    }
    
    /**
     * Enable automatic IDE helper generation
     * 
     * @param string|null $outputPath Custom output path for .ide-helper.php
     */
    public function enable(?string $outputPath = null): void
    {
        $this->enabled = true;
        
        if ($outputPath !== null) {
            $this->outputPath = $outputPath;
        }
    }
    
    /**
     * Disable automatic IDE helper generation
     */
    public function disable(): void
    {
        $this->enabled = false;
    }
    
    /**
     * Check if IDE helper is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
    
    /**
     * Register a custom strategy and trigger regeneration if enabled
     * 
     * @param ValidationStrategyInterface $strategy
     */
    public function registerStrategy(ValidationStrategyInterface $strategy): void
    {
        if (!$this->enabled) {
            return;
        }
        
        $this->registeredStrategies[] = $strategy;
        $this->regenerate();
    }

    /**
     * Notify that a rule was registered and trigger regeneration
     */
    public function notifyRuleRegistered(): void
    {
        if (!$this->enabled) {
            return;
        }
        
        $this->regenerate();
    }

    /**
     * Notify that a schema was registered and trigger regeneration
     */
    public function notifySchemaRegistered(): void
    {
        if (!$this->enabled) {
            return;
        }
        
        $this->regenerate();
    }
    
    /**
     * Manually regenerate IDE helper file
     * 
     * @return bool Success
     */
    public function regenerate(): bool
    {
        if (!$this->enabled) {
            return false;
        }
        
        // Check writable
        $dir = dirname($this->outputPath);
        if (!is_dir($dir) || !is_writable($dir)) {
            return false;
        }
        
        // Debounce: max once per second (unless disabled for testing)
        if ($this->debounceEnabled && time() - $this->lastGenerated < 1) {
            return false;
        }
        
        $this->lastGenerated = time();
        
        // Generate with error suppression
        try {
            $content = GeneratePHPDoc::generateCompleteIdeHelper($this->registeredStrategies);
            return $this->writeFile($content);
        } catch (\Throwable $e) {
            // Silent fail - never crash the app for IDE hints
            if (function_exists('error_log')) {
                error_log("DataVerify IDE helper generation failed: {$e->getMessage()}");
            }
            
            return false;
        }
    }

    /**
     * Write content to IDE helper file (thread-safe)
     */
    private function writeFile(string $content): bool
    {
        $fp = @fopen($this->outputPath, 'c');
        if ($fp === false) {
            return false;
        }
        
        try {
            if (flock($fp, LOCK_EX)) {
                ftruncate($fp, 0);
                rewind($fp);
                $result = fwrite($fp, $content) !== false;
                flock($fp, LOCK_UN);
                return $result;
            }
            return false;
        } finally {
            fclose($fp);
        }
    }
    
    /**
     * Get currently registered strategies
     * 
     * @return array
     */
    public function getRegisteredStrategies(): array
    {
        return $this->registeredStrategies;
    }
    
    /**
     * Clear all registered strategies
     */
    public function clear(): void
    {
        $this->registeredStrategies = [];
    }
    
    /**
     * Get output path
     */
    public function getOutputPath(): string
    {
        return $this->outputPath;
    }

    /**
     * Reset singleton instance (for testing)
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}