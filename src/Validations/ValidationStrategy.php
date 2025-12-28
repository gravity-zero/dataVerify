<?php
namespace Gravity\Validations;

use ReflectionMethod;
use Gravity\Interfaces\ValidationStrategyInterface;

/**
 * Base class for all validation strategies
 * 
* @method protected bool handler(mixed $value, mixed ...$args) Implement your validation logic here with named parameters
 * 
 * @example Validation with named parameters
 * ```php
 * class BetweenStrategy extends ValidationStrategy
 * {
 *     public function getName(): string { return 'between'; }
 *     
 *     protected function handler(mixed $value, int $min, int $max): bool
 *     {
 *         return is_numeric($value) && $value >= $min && $value <= $max;
 *     }
 * }
 * // Translation: "The {field} must be between {min} and {max}"
 * ```
 */
abstract class ValidationStrategy implements ValidationStrategyInterface
{
    /**
     * Reflection method cache (one per class)
     * @var array<string, ReflectionMethod>
     */
    private static array $reflectionCache = [];
    
    /**
     * Get the name of the validation rule
     */
    abstract public function getName(): string;
    
    /**
     * @internal
     * @deprecated DO NOT override this method. Implement handler() instead.
     * 
     * Execute validation with uniform signature
     * Called by the framework with array of arguments
     * 
     * @param mixed $value The value to validate
     * @param array $args Additional arguments
     * @return bool True if validation passes
     */
    final public function execute(mixed $value, array $args): bool
    {
        $class = static::class;
        
        // Cache reflection for performance
        if (!isset(self::$reflectionCache[$class])) {
            self::$reflectionCache[$class] = new ReflectionMethod($this, 'handler');
        }
        
        // Call handler() with reflection (supports any signature)
        return self::$reflectionCache[$class]->invokeArgs($this, array_merge([$value], $args));
    }
}
