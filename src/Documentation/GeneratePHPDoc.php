<?php

namespace Gravity\Documentation;

use Gravity\Registry\LazyValidationRegistry;

class GeneratePHPDoc
{
    /**
     * Generate PHPDoc annotations for DataVerify class
     * 
     * @return string PHPDoc block ready to paste into DataVerify.php
     */
    public static function generate(): string
    {
        $registry = LazyValidationRegistry::instance();
        
        // Discover all validations by scanning the Validations directory
        $validations = self::discoverAllValidations($registry);
        
        // Separate into properties (no required params) and methods (with params)
        $properties = [];
        $methods = [];
        
        foreach ($validations as $name => $metadata) {
            $params = $metadata->getParameters();
            
            // Check if has required parameters
            $hasRequiredParams = false;
            foreach ($params as $param) {
                if ($param['required']) {
                    $hasRequiredParams = true;
                    break;
                }
            }
            
            // If no required params, it can be used as @property
            // (even if it has optional params, e.g., date(), boolean())
            if (!$hasRequiredParams) {
                $properties[] = [
                    'name' => $name,
                    'description' => $metadata->getDescription()
                ];
            }
            
            // If it has ANY params (required or optional), also add as @method
            if (!empty($params)) {
                $methods[] = [
                    'name' => $name,
                    'params' => self::formatParameters($params),
                    'description' => $metadata->getDescription()
                ];
            }
        }
        
        // Sort alphabetically
        usort($properties, fn($a, $b) => strcmp($a['name'], $b['name']));
        usort($methods, fn($a, $b) => strcmp($a['name'], $b['name']));
        
        // Generate PHPDoc
        return self::buildPhpDoc($properties, $methods);
    }
    
    /**
     * Discover all validation classes
     */
    private static function discoverAllValidations(LazyValidationRegistry $registry): array
    {
        $validations = [];
        $baseDir = dirname(__DIR__) . '/Validations';
        
        if (!is_dir($baseDir)) {
            return $validations;
        }
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($baseDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());
                
                // Extract validation name from getName() method
                if (preg_match('/public function getName\(\):\s*string\s*\{\s*return\s+[\'"]([a-zA-Z]+)[\'"];/s', $content, $matches)) {
                    $name = $matches[1];
                    $metadata = $registry->get($name);
                    
                    if ($metadata) {
                        $validations[$name] = $metadata;
                    }
                }
            }
        }
        
        return $validations;
    }
    
    /**
     * Format method parameters for PHPDoc
     */
    private static function formatParameters(array $params): string
    {
        if (empty($params)) {
            return '';
        }
        
        $formatted = [];
        
        foreach ($params as $param) {
            $type = $param['type'];
            $name = $param['name'];
            
            if ($param['required']) {
                $formatted[] = "{$type} \${$name}";
            } else {
                $default = $param['default'] ?? null;
                $defaultValue = self::formatDefaultValue($default);
                $formatted[] = "{$type} \${$name} = {$defaultValue}";
            }
        }
        
        return implode(', ', $formatted);
    }
    
    /**
     * Format default value for PHPDoc
     */
    private static function formatDefaultValue(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }
        
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        
        if (is_string($value)) {
            return "'{$value}'";
        }
        
        if (is_array($value)) {
            return empty($value) ? '[]' : '[...]';
        }
        
        return var_export($value, true);
    }
    
    /**
     * Build final PHPDoc block
     */
    private static function buildPhpDoc(array $properties, array $methods): string
    {
        $lines = [];
        $lines[] = '/**';
        $lines[] = ' * Class DataVerify';
        $lines[] = ' *';
        $lines[] = ' * Main validation class using lazy-loaded validation strategies';
        $lines[] = ' *';
        
        // Add @property annotations
        foreach ($properties as $prop) {
            $lines[] = sprintf(
                ' * @property DataVerify $%s %s',
                $prop['name'],
                $prop['description']
            );
        }
        
        // Add special property for conditional validation
        $lines[] = ' * @property DataVerify $then Activate conditional validation mode';
        
        $lines[] = ' *';
        
        // Add @method annotations
        foreach ($methods as $method) {
            $lines[] = sprintf(
                ' * @method DataVerify %s(%s) %s',
                $method['name'],
                $method['params'],
                $method['description']
            );
        }
        
        $lines[] = ' */';
        
        return implode("\n", $lines);
    }
    
    /**
     * Write generated PHPDoc to DataVerify.php
     * 
     * @param string $filePath Path to DataVerify.php
     * @return bool Success
     */
    public static function updateDataVerifyFile(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("DataVerify.php not found at: {$filePath}");
        }
        
        $content = file_get_contents($filePath);
        $newPhpDoc = self::generate();
        
        // Replace existing PHPDoc (everything between /** and */ before "class DataVerify")
        $pattern = '/\/\*\*.*?\*\/\s*class\s+DataVerify/s';
        
        if (preg_match($pattern, $content)) {
            $updated = preg_replace(
                $pattern,
                $newPhpDoc . "\nclass DataVerify",
                $content
            );
            
            return file_put_contents($filePath, $updated) !== false;
        }
        
        return false;
    }
    
    /**
     * Generate IDE helper stub file for custom strategies
     * This file is never loaded at runtime, only used by IDEs for autocompletion
     * 
     * @param array $customStrategies Array of custom ValidationStrategyInterface instances
     * @return string Content of .ide-helper.php
     */
    public static function generateIdeHelper(array $customStrategies = []): string
    {
        $methods = [];
        
        foreach ($customStrategies as $strategy) {
            if (!($strategy instanceof \Gravity\Interfaces\ValidationStrategyInterface)) {
                continue;
            }
            
            $name = $strategy->getName();
            
            // Try to get parameters via reflection
            $reflection = new \ReflectionClass($strategy);
            $handlerMethod = $reflection->getMethod('handler');
            $params = $handlerMethod->getParameters();
            
            // Skip first parameter ($value)
            array_shift($params);
            
            $paramSignature = [];
            foreach ($params as $param) {
                $type = $param->getType() ? $param->getType()->getName() : 'mixed';
                $paramName = $param->getName();
                
                if ($param->isOptional()) {
                    $default = $param->isDefaultValueAvailable() 
                        ? var_export($param->getDefaultValue(), true) 
                        : 'null';
                    $paramSignature[] = "{$type} \${$paramName} = {$default}";
                } else {
                    $paramSignature[] = "{$type} \${$paramName}";
                }
            }
            
            $methods[] = sprintf(
                "     * @method DataVerify %s(%s) Custom validation: %s",
                $name,
                implode(', ', $paramSignature),
                $name
            );
        }
        
        $methodsBlock = empty($methods) 
            ? ''
            : "\n     *\n" . implode("\n", $methods);
        
        return sprintf(
            <<<'STUB'
<?php
/**
 * IDE Helper for DataVerify Custom Validations
 * 
 * This file is generated automatically and should not be edited manually.
 * It provides autocompletion for custom validation strategies.
 * 
 * @see \Gravity\DataVerify
 */

namespace Gravity {
    /**
     * Custom validation strategies%s
     */
    class DataVerify {}
}

STUB,
            $methodsBlock
        );
    }
    
    /**
     * Write IDE helper file (thread-safe with file locking)
     * 
     * @param string $outputPath Path where to write .ide-helper.php
     * @param array $customStrategies Custom strategies to include
     * @return bool Success
     */
    public static function writeIdeHelper(string $outputPath, array $customStrategies = []): bool
    {
        $content = self::generateIdeHelper($customStrategies);
        
        // Atomic write with exclusive lock
        $fp = @fopen($outputPath, 'c');
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
}