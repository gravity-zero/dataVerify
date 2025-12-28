<?php

namespace Gravity\Registry;

use Gravity\Attributes\{ValidationRule, Param};
use Gravity\Interfaces\ValidationStrategyInterface;
use ReflectionClass;
use ReflectionMethod;

/**
 * Discovers validations from classes using attributes
 * 
 * Supports discovery from:
 * - ValidationMethods class (protected methods with attributes)
 * - ValidationStrategyInterface implementations (optional attributes)
 */
class ValidationDiscovery
{
    /**
     * Discover all validations from a class with ValidationRule attributes
     * 
     * @return array<string, ValidationMetadata>
     */
    public static function discoverFromClass(object $instance): array
    {
        $reflection = new ReflectionClass($instance);
        $discovered = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PROTECTED | ReflectionMethod::IS_PUBLIC) as $method) {
            $attributes = $method->getAttributes(ValidationRule::class);
            
            if (empty($attributes)) {
                continue;
            }

            /** @var ValidationRule $rule */
            $rule = $attributes[0]->newInstance();
            
            $name = $rule->name ?? self::inferNameFromMethod($method->getName());
            
            $parameters = self::extractParameters($method);
            
            $discovered[$name] = new ValidationMetadata(
                name: $name,
                callable: $method->getClosure($instance),
                description: $rule->description,
                category: $rule->category,
                examples: $rule->examples,
                parameters: $parameters
            );
        }

        return $discovered;
    }

    /**
     * Discover metadata from a ValidationStrategyInterface
     * 
     * Attributes are optional - if not present, returns basic metadata
     */
    public static function discoverFromStrategy(ValidationStrategyInterface $strategy): ValidationMetadata
    {
        $reflection = new ReflectionClass($strategy);
        $attributes = $reflection->getAttributes(ValidationRule::class);
        
        $callable = function($value, ...$args) use ($strategy) {
            return $strategy->execute($value, $args);
        };
        
        if (empty($attributes)) {
            return new ValidationMetadata(
                name: $strategy->getName(),
                callable: $callable
            );
        }

        /** @var ValidationRule $rule */
        $rule = $attributes[0]->newInstance();
        
        // ✅ Chercher handler() parmi les méthodes protégées aussi
        try {
            $handlerMethod = $reflection->getMethod('handler');
            $parameters = self::extractParameters($handlerMethod);
        } catch (\ReflectionException $e) {
            // handler() n'existe pas → ancienne custom strategy
            $parameters = [];
        }
        
        return new ValidationMetadata(
            name: $strategy->getName(),
            callable: $callable,
            description: $rule->description,
            category: $rule->category,
            examples: $rule->examples,
            parameters: $parameters
        );
    }

    /**
     * Infer validation name from method name
     * 
     * validateEmail -> email
     * validateMinLength -> min_length
     */
    private static function inferNameFromMethod(string $methodName): string
    {
        // Remove "handler" prefix if present
        if (str_starts_with($methodName, 'handler')) {
            $name = substr($methodName, 8);
            // Convert camelCase to snake_case
            return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $name));
        }
        
        return $methodName;
    }

    /**
     * Extract parameter metadata from a method
     * 
     * @return array<array{name: string, type: string, required: bool, default: mixed, description: ?string, example: mixed}>
     */
    private static function extractParameters(ReflectionMethod $method): array
    {
        $parameters = [];
            
        foreach ($method->getParameters() as $param) {
            if ($param->getPosition() === 0) {
                continue;
            }
            
            $paramAttrs = $param->getAttributes(Param::class);
            $paramMetadata = !empty($paramAttrs) ? $paramAttrs[0]->newInstance() : null;
            
            $type = 'mixed';
            if ($param->hasType()) {
                $reflectionType = $param->getType();
                if ($reflectionType instanceof \ReflectionNamedType) {
                    $type = $reflectionType->getName();
                } elseif ($reflectionType instanceof \ReflectionUnionType) {
                    $types = array_map(fn($t) => $t->getName(), $reflectionType->getTypes());
                    $type = implode('|', $types);
                }
            }
            
            $parameters[] = [
                'name' => $param->getName(),
                'type' => $type,
                'required' => !$param->isOptional(),
                'default' => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
                'description' => $paramMetadata?->description ?? null,
                'example' => $paramMetadata?->example ?? null
            ];
        }
    
    return $parameters;
    }
}
