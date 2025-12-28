<?php

namespace Gravity\Documentation;

use Gravity\Registry\{ValidationRegistry, ValidationMetadata};

class DocumentationGenerator
{
    /**
     * Generate markdown documentation with details/summary
     */
    public static function generate(ValidationRegistry $registry): string
    {
        $validations = $registry->getAllWithMetadata();
        
        if (empty($validations)) {
            return "No validations registered.\n";
        }

        $byCategory = self::groupByCategory($validations);
        
        $doc = "# Validation Rules Reference\n\n";
        $doc .= "Complete reference of all **" . count($validations) . "** built-in validation rules.\n\n";
        
        // Table of Contents
        $doc .= self::generateTableOfContents($byCategory);
        
        // Category sections
        foreach ($byCategory as $category => $categoryValidations) {
            $doc .= self::generateCategorySection($category, $categoryValidations);
        }
        
        // Footer
        $doc .= self::generateFooter();
        
        return $doc;
    }

    /**
     * Generate simple list format
     */
    public static function generateList(ValidationRegistry $registry): string
    {
        $validations = $registry->getAll();
        sort($validations);
        
        return "Available validations:\n- " . implode("\n- ", $validations) . "\n";
    }

    /**
     * Generate JSON format
     */
    public static function generateJson(ValidationRegistry $registry): string
    {
        $validations = $registry->getAllWithMetadata();
        
        $data = [];
        foreach ($validations as $name => $metadata) {
            $data[$name] = $metadata->toArray();
        }
        
        return json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * Generate table of contents
     */
    private static function generateTableOfContents(array $byCategory): string
    {
        $toc = "## Table of Contents\n\n";
        
        foreach ($byCategory as $category => $validations) {
            $anchor = strtolower(str_replace(' ', '-', $category));
            $count = count($validations);
            $toc .= "- [{$category}](#{$anchor}) ({$count} rules)\n";
        }
        
        $toc .= "\n---\n\n";
        
        return $toc;
    }

    /**
     * Generate a category section with details/summary
     */
    private static function generateCategorySection(string $category, array $validations): string
    {
        $doc = "## {$category}\n\n";
        
        foreach ($validations as $metadata) {
            $doc .= self::generateValidationEntry($metadata);
        }
        
        return $doc;
    }

    /**
     * Generate a single validation entry with details/summary
     */
    private static function generateValidationEntry(ValidationMetadata $metadata): string
    {
        // Title without backticks for better rendering
        $title = $metadata->name;
        
        // One-line description for summary
        $shortDesc = $metadata->description 
            ? (strlen($metadata->description) > 70 
                ? substr($metadata->description, 0, 70) . '...' 
                : $metadata->description)
            : 'Validation rule';
        
        $doc = "<details>\n";
        $doc .= "<summary><code>{$title}</code> - {$shortDesc}</summary>\n\n";
        
        // Full description if different from short
        if ($metadata->description && strlen($metadata->description) > 70) {
            $doc .= "**Description:**\n\n";
            $doc .= "{$metadata->description}\n\n";
        }
        
        // Parameters
        if (!empty($metadata->parameters)) {
            $doc .= "**Parameters:**\n\n";
            $doc .= "<table>\n";
            $doc .= "<tr><th>Name</th><th>Type</th><th>Required</th><th>Description</th></tr>\n";
            
            foreach ($metadata->parameters as $param) {
                $required = $param['required'] ? '✓' : '✗';
                $desc = $param['description'] ?? '';
                
                // Add example and default to description
                if ($param['example'] !== null) {
                    $exampleStr = is_array($param['example']) 
                        ? json_encode($param['example']) 
                        : var_export($param['example'], true);
                    $desc .= "<br><em>Example: <code>{$exampleStr}</code></em>";
                }
                
                if (!$param['required'] && $param['default'] !== null) {
                    $defaultStr = is_array($param['default'])
                        ? json_encode($param['default'])
                        : var_export($param['default'], true);
                    $desc .= "<br><em>Default: <code>{$defaultStr}</code></em>";
                }
                
                $doc .= "<tr>";
                $doc .= "<td><code>{$param['name']}</code></td>";
                $doc .= "<td><code>{$param['type']}</code></td>";
                $doc .= "<td align=\"center\">{$required}</td>";
                $doc .= "<td>{$desc}</td>";
                $doc .= "</tr>\n";
            }
            
            $doc .= "</table>\n\n";
        }
        
        // Examples
        if (!empty($metadata->examples)) {
            $doc .= "**Usage:**\n\n";
            foreach ($metadata->examples as $example) {
                $doc .= "```php\n{$example}\n```\n\n";
            }
        }
        
        $doc .= "</details>\n\n";
        
        return $doc;
    }

    /**
     * Generate footer with links
     */
    private static function generateFooter(): string
    {
        return <<<MD
---

## See Also

- **[Error Handling](ERROR_HANDLING.md)** - Working with validation errors
- **[Conditional Validation](CONDITIONAL_VALIDATION.md)** - Apply rules conditionally
- **[Custom Strategies](CUSTOM_STRATEGIES.md)** - Create your own validations
- **[Internationalization](INTERNATIONALIZATION.md)** - Translate error messages

MD;
    }

    /**
     * Group validations by category
     */
    private static function groupByCategory(array $validations): array
    {
        $byCategory = [];
        
        foreach ($validations as $metadata) {
            $category = $metadata->category ?? 'Other';
            
            if (!isset($byCategory[$category])) {
                $byCategory[$category] = [];
            }
            
            $byCategory[$category][] = $metadata;
        }
        
        ksort($byCategory);
        
        return $byCategory;
    }
}