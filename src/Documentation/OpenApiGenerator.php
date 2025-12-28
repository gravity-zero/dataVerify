<?php

namespace Gravity\Documentation;

use Gravity\Registry\{ValidationRegistry, ValidationMetadata};

class OpenApiGenerator
{
    public static function generate(ValidationRegistry $registry, array $options = []): string
    {
        $validations = $registry->getAllWithMetadata();
        $categories = self::groupByCategory($validations);
        
        $total = array_sum(array_map('count', $categories));
        
        $schema = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => $options['title'] ?? 'DataVerify Validation Rules',
                'description' => "Complete reference of {$total} validation rules. See above for usage examples.",
                'version' => $options['version'] ?? '1.0.0'
            ],
            'components' => [
                'schemas' => self::generateSchemas($registry)
            ]
        ];
        
        return json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public static function generateJsonSchema(ValidationRegistry $registry): string
    {
        $schema = [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            'title' => 'DataVerify Validations',
            'type' => 'object',
            'properties' => self::generateSchemas($registry)
        ];
        
        return json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public static function generateApiPlatform(ValidationRegistry $registry): string
    {
        $output = "<?php\n\n";
        $output .= "/**\n";
        $output .= " * DataVerify Validation Constraints\n";
        $output .= " * \n";
        $output .= " * Use these annotations in your API Platform entities:\n";
        $output .= " */\n\n";
        
        $validations = $registry->getAllWithMetadata();
        
        foreach ($validations as $name => $metadata) {
            $output .= self::generateApiPlatformAnnotation($metadata);
        }
        
        return $output;
    }

    public static function generateSwaggerUI(ValidationRegistry $registry, array $options = []): string
    {
        $title = $options['title'] ?? 'DataVerify Validations';
        $schemaUrl = $options['schemaUrl'] ?? './openapi.json';
        
        $validations = $registry->getAllWithMetadata();
        $categories = self::groupByCategory($validations);
        $descriptionHtml = self::generateDescriptionHtml($categories);
        
        return <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>{$title}</title>
        <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css">
        <style>
            body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
            .swagger-ui .topbar { display: none; }
            .custom-header { 
                padding: 40px 60px; 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
            }
            .custom-header h1 { margin: 0 0 20px 0; font-size: 2.5em; }
            .custom-header p { margin: 0; font-size: 1.1em; opacity: 0.9; }
            .custom-content { padding: 40px 60px; max-width: 1200px; }
            .custom-content h2 { 
                margin: 40px 0 20px 0; 
                font-size: 1.8em; 
                border-bottom: 2px solid #667eea;
                padding-bottom: 10px;
            }
            .custom-content h2:first-child { margin-top: 0; }
            .category-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 10px; }
            .category-item { 
                padding: 12px 16px; 
                background: #f8f9fa; 
                border-radius: 6px; 
                border-left: 4px solid #667eea;
            }
            .category-item strong { color: #667eea; }
            pre[class*="language-"] { border-radius: 8px; margin: 20px 0; }
            code[class*="language-"] { font-size: 0.9em; }
            .feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0; }
            .feature-card { padding: 20px; background: #f8f9fa; border-radius: 8px; }
            .feature-card h3 { margin-top: 0; color: #667eea; }
            .swagger-ui { margin-top: 40px; }
        </style>
    </head>
    <body>
        <div class="custom-header">
            <h1>📚 {$title}</h1>
            <p>Complete reference of validation rules with examples and documentation</p>
        </div>
        
        <div class="custom-content">
            {$descriptionHtml}
        </div>
        
        <div id="swagger-ui"></div>
        
        <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-markup-templating.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-php.min.js"></script>
        <script>
            window.onload = function() {
                SwaggerUIBundle({
                    url: '{$schemaUrl}',
                    dom_id: '#swagger-ui',
                    deepLinking: true,
                    docExpansion: 'list',
                    defaultModelsExpandDepth: 2,
                    presets: [
                        SwaggerUIBundle.presets.apis,
                        SwaggerUIBundle.SwaggerUIStandalonePreset
                    ]
                });
                Prism.highlightAll();
            };
        </script>
    </body>
    </html>
    HTML;
    }

    private static function generateDescriptionHtml(array $categories): string
    {
        $total = array_sum(array_map('count', $categories));
        
        $html = '<h2>📖 Quick Start</h2>';
        $html .= '<pre><code class="language-php">use Gravity\DataVerify;

$data = (object)[
    \'email\' => \'user@example.com\',
    \'age\' => 25,
    \'password\' => \'Secret123!\',
    \'type\' => \'premium\'
];

$dv = new DataVerify($data);

// Chain validations
$dv
    ->field(\'email\')->required->email
    ->field(\'age\')->required->int->between(0, 120)
    ->field(\'password\')->required->minLength(8)->containsUpper->containsNumber;

// Run validation
$isValid = $dv->verify();

// Get errors
if (!$isValid) {
    $errors = $dv->getErrors();
}</code></pre>';
        
        $html .= '<h2>📚 Categories</h2>';
        $html .= '<p>Browse <strong>' . $total . '</strong> validation rules organized in <strong>' . count($categories) . '</strong> categories:</p>';
        $html .= '<div class="category-list">';
        foreach ($categories as $category => $validations) {
            $count = count($validations);
            $html .= '<div class="category-item"><strong>' . $category . '</strong> (' . $count . ' rules)</div>';
        }
        $html .= '</div>';
        
        $html .= '<h2>🌳 Nested Objects (Subfields)</h2>';
        $html .= '<p>Validate nested data structures with dot notation:</p>';
        $html .= '<pre><code class="language-php">$data->user = (object)[
    \'name\' => \'John\',
    \'email\' => \'john@example.com\',
    \'address\' => (object)[
        \'city\' => \'Paris\',
        \'country\' => \'FR\'
    ]
];

$dv = new DataVerify($data);
$dv
    ->field(\'user\')->required->object
        ->subfield(\'name\')->required->string
        ->subfield(\'email\')->required->email
        ->subfield(\'address\')->required->object
            ->subfield(\'address\', \'city\')->required->string
            ->subfield(\'address\', \'country\')->in([\'FR\', \'DE\', \'IT\']);</code></pre>';
            
        $html .= '<h2>⚙️ Validation Modes</h2>';
        $html .= '<p>Choose between collecting all errors or stopping at the first one:</p>';
        $html .= '<pre><code class="language-php">// Batch mode (default): Collect all errors
$dv->verify();  // Returns all validation errors

// Fail-fast mode: Stop at first error
$dv->verify(false);  // Returns only first error</code></pre>';
        
        $html .= '<h2>🔄 Instance Lifecycle</h2>';
        $html .= '<p><strong>Important:</strong> Each instance can only call <code>verify()</code> once. Create a new instance for each validation.</p>';
        $html .= '<pre><code class="language-php">// ✅ Correct: One instance per validation
$dv1 = new DataVerify($data);
$dv1->field(\'email\')->required;
$dv1->verify();

$dv2 = new DataVerify($data);  // New instance
$dv2->field(\'password\')->required;
$dv2->verify();

// ❌ Incorrect: Reusing instance
$dv3 = new DataVerify($data);
$dv3->verify();
$dv3->verify();  // Throws LogicException!</code></pre>';
            
        $html .= '<h2>❌ Error Handling</h2>';
        $html .= '<p>Errors are returned with detailed information:</p>';
        $html .= '<pre><code class="language-php">if (!$dv->verify()) {
    // Get errors as arrays
    $errors = $dv->getErrors();
    // [
    //     [
    //         \'field\' => \'email\',
    //         \'alias\' => \'Email Address\',
    //         \'test\' => \'email\',
    //         \'message\' => \'The field Email Address must be a valid email\',
    //         \'value\' => \'invalid\'
    //     ]
    // ]

    // Or as objects
    $errors = $dv->getErrors(true);
    foreach ($errors as $error) {
        echo $error->message;
    }
}</code></pre>';
        
        $html .= '<h2>⚡ Conditional Validations</h2>';
        $html .= '<p>Apply validations based on other field values:</p>';
        $html .= '<pre><code class="language-php">// Require \'discount_code\' only if type is \'premium\'
$dv->field(\'discount_code\')
    ->when(\'type\', \'=\', \'premium\')
    ->then->required;

// Complex conditions with AND/OR
$dv->field(\'vat_number\')
    ->when(\'country\', \'in\', [\'FR\', \'DE\'])
    ->and(\'type\', \'=\', \'business\')
    ->then->required->regex(\'/^[A-Z]{2}[0-9]+$/\');

// Operators: =, !=, in, not_in, >, <, >=, <=</code></pre>';
        
        $html .= '<div class="feature-grid">';
        
        $html .= '<div class="feature-card">';
        $html .= '<h3>🔧 Custom Validations</h3>';
        $html .= '<pre><code class="language-php">use Gravity\Validations\ValidationStrategy;
use Gravity\Attributes\ValidationRule;

#[ValidationRule(
    description: \'Validates UUID v4\',
    category: \'Custom\'
)]
class UuidValidation extends ValidationStrategy
{
    public function getName(): string
    {
        return \'uuid\';
    }
    
    protected function handler(mixed $value): bool
    {
        return preg_match(\'/^[0-9a-f-]{36}$/i\', $value);
    }
}

$dv->registerStrategy(new UuidValidation());</code></pre>';
        $html .= '</div>';
        
        $html .= '<div class="feature-card">';
        $html .= '<h3>🌍 Internationalization</h3>';
        $html .= '<pre><code class="language-php">// Switch to French
$dv->setLocale(\'fr\');

// Add custom translations
$dv->addTranslations([
    \'validation.required\' => \'El campo {field} es obligatorio\'
], \'es\');

// Placeholders: {field}, {min}, {max}, {value}</code></pre>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        $html .= '<h2>📝 Optional Fields</h2>';
        $html .= '<p>Fields without <code>required</code> accept null values. Validations only run when a value is present:</p>';
        $html .= '<pre><code class="language-php">$data->email = null;  // Optional field

$dv->field(\'email\')->email;  // ✓ Passes (null skipped)

$data->email = \'invalid\';  // Invalid value
$dv->field(\'email\')->email;  // ✗ Fails</code></pre>';
        
        return $html;
    }


    private static function groupByCategory(array $validations): array
    {
        $categories = [];
        foreach ($validations as $metadata) {
            $category = $metadata->category ?? 'Other';
            if (!isset($categories[$category])) {
                $categories[$category] = [];
            }
            $categories[$category][] = $metadata;
        }
        ksort($categories);
        return $categories;
    }

    private static function generateSchemas(ValidationRegistry $registry): array
    {
        $schemas = [];
        $validations = $registry->getAllWithMetadata();
        
        foreach ($validations as $name => $metadata) {
            $schemas[$name] = self::generateSchema($metadata);
        }
        
        return $schemas;
    }

    private static function generateSchema(ValidationMetadata $metadata): array
    {
        $schema = [
            'type' => 'object',
            'title' => ucfirst(str_replace('_', ' ', $metadata->name)),
            'description' => $metadata->description ?? "Validation rule: {$metadata->name}",
        ];

        if ($metadata->category) {
            $schema['x-category'] = $metadata->category;
        }

        $examplesBlock = [];
        
        if (!empty($metadata->examples)) {
            $examplesBlock[] = "**Usage:**";
            foreach ($metadata->examples as $example) {
                $examplesBlock[] = "```php\n{$example}\n```";
            }
        }

        if (!empty($metadata->parameters)) {
            $schema['properties'] = [];
            $schema['required'] = [];
            
            $examplesBlock[] = "\n**Parameters:**";
            
            foreach ($metadata->parameters as $param) {
                $paramSchema = [
                    'description' => $param['description'] ?? "Parameter: {$param['name']}",
                ];
                
                $typeMapping = self::mapTypeToJsonSchema($param['type']);
                
                if (is_array($typeMapping)) {
                    $paramSchema = array_merge($paramSchema, $typeMapping);
                } else {
                    $paramSchema['type'] = $typeMapping;
                }
                
                if ($param['example'] !== null) {
                    $paramSchema['example'] = $param['example'];
                    
                    $exampleStr = is_scalar($param['example']) 
                        ? var_export($param['example'], true)
                        : json_encode($param['example']);
                    $examplesBlock[] = "- `{$param['name']}`: {$exampleStr}";
                }
                
                if (!$param['required'] && $param['default'] !== null) {
                    $paramSchema['default'] = $param['default'];
                }
                
                $schema['properties'][$param['name']] = $paramSchema;
                
                if ($param['required']) {
                    $schema['required'][] = $param['name'];
                }
            }
            
            if (empty($schema['required'])) {
                unset($schema['required']);
            }
        }

        if (!empty($examplesBlock)) {
            $schema['description'] .= "\n\n" . implode("\n", $examplesBlock);
        }

        return $schema;
    }

    private static function generateApiPlatformAnnotation(ValidationMetadata $metadata): string
    {
        $name = ucfirst(str_replace('_', '', ucwords($metadata->name, '_')));
        $output = "/**\n";
        $output .= " * @Assert\\{$name}";
        
        if (!empty($metadata->parameters)) {
            $output .= "(\n";
            $params = [];
            foreach ($metadata->parameters as $param) {
                $example = $param['example'] ?? ($param['default'] ?? 'null');
                $params[] = " *     {$param['name']}={$example}";
            }
            $output .= implode(",\n", $params) . "\n";
            $output .= " * )";
        }
        
        $output .= "\n";
        
        if ($metadata->description) {
            $output .= " * Description: {$metadata->description}\n";
        }
        
        $output .= " */\n\n";
        
        return $output;
    }

    private static function mapTypeToJsonSchema(string $phpType): string|array
    {
        if (str_contains($phpType, '|')) {
            return self::mapUnionType($phpType);
        }
        
        return match($phpType) {
            'int', 'integer' => 'integer',
            'float', 'double' => 'number',
            'string' => 'string',
            'bool', 'boolean' => 'boolean',
            'array' => 'array',
            'object' => 'object',
            'DateTime' => 'string',
            'mixed' => 'string',
            default => 'string'
        };
    }

    private static function mapUnionType(string $unionType): array
    {
        $types = explode('|', $unionType);
        $jsonTypes = [];
        
        foreach ($types as $type) {
            $type = trim($type);
            
            $mapped = match($type) {
                'int', 'integer' => 'integer',
                'float', 'double' => 'number',
                'string' => 'string',
                'bool', 'boolean' => 'boolean',
                'array' => 'array',
                'object' => 'object',
                'DateTime' => 'string',
                'mixed' => 'string',
                default => 'string'
            };
            
            if (!in_array($mapped, $jsonTypes)) {
                $jsonTypes[] = $mapped;
            }
        }
        
        if (count($jsonTypes) === 1) {
            return ['type' => $jsonTypes[0]];
        }
        
        return ['oneOf' => array_map(fn($t) => ['type' => $t], $jsonTypes)];
    }
}