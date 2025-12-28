#!/usr/bin/env php
<?php

/**
 * DataVerify Documentation Generator
 * 
 * Generates all documentation formats for DataVerify validations
 * and updates PHPDoc in DataVerify.php
 */

require __DIR__ . '/vendor/autoload.php';

use Gravity\DataVerify;
use Gravity\Documentation\{DocumentationGenerator, OpenApiGenerator, GeneratePHPDoc};

// Colors for terminal output
const GREEN = "\033[32m";
const BLUE = "\033[34m";
const YELLOW = "\033[33m";
const RED = "\033[31m";
const RESET = "\033[0m";

function info(string $msg): void {
    echo BLUE . "ℹ " . $msg . RESET . "\n";
}

function success(string $msg): void {
    echo GREEN . "✓ " . $msg . RESET . "\n";
}

function error(string $msg): void {
    echo RED . "✗ " . $msg . RESET . "\n";
}

function section(string $msg): void {
    echo "\n" . YELLOW . "═══ " . $msg . " ═══" . RESET . "\n";
}

// Parse arguments
$updatePhpDoc = in_array('--phpdoc', $argv) || in_array('-p', $argv);
$skipDocs = in_array('--skip-docs', $argv);

// 1. Update PHPDoc in DataVerify.php
if ($updatePhpDoc || !$skipDocs) {
    section("Updating PHPDoc");
    
    try {
        info("Generating PHPDoc for DataVerify.php...");
        $result = GeneratePHPDoc::updateDataVerifyFile(__DIR__ . '/src/DataVerify.php');
        
        if ($result) {
            success("PHPDoc updated in src/DataVerify.php");
        } else {
            error("Failed to update PHPDoc");
            exit(1);
        }
    } catch (\Throwable $e) {
        error("Error: " . $e->getMessage());
        exit(1);
    }
}

// 2. Generate documentation files
if (!$skipDocs) {
    // Create output directory
    $docsDir = __DIR__ . '/docs';
    if (!is_dir($docsDir)) {
        mkdir($docsDir, 0755, true);
        info("Created docs/ directory");
    }

    // Initialize DataVerify to access registry
    $verifier = new DataVerify(new stdClass());
    $registry = $verifier->listValidations();

    section("Generating Documentation");
    info("Total validations: " . count($registry));

    // Markdown Documentation
    info("Generating Markdown documentation...");
    $markdown = $verifier->generateDocumentation('markdown');
    file_put_contents($docsDir . '/VALIDATIONS.md', $markdown);
    success("Created docs/VALIDATIONS.md");

    // JSON Schema
    info("Generating JSON Schema...");
    $jsonSchema = $verifier->generateJsonSchema();
    file_put_contents($docsDir . '/schema.json', $jsonSchema);
    success("Created docs/schema.json");

    // OpenAPI Schema
    info("Generating OpenAPI schema...");
    $openapi = $verifier->generateOpenApiSchema([
        'title' => 'DataVerify Validation Rules',
        'description' => 'Complete reference of all available validation rules',
        'version' => '1.0.0'
    ]);
    file_put_contents($docsDir . '/openapi.json', $openapi);
    success("Created docs/openapi.json");

    // Swagger UI
    info("Generating Swagger UI...");
    $swaggerUI = $verifier->generateSwaggerUI([
        'title' => 'DataVerify Validations',
        'schemaUrl' => './openapi.json'
    ]);
    file_put_contents($docsDir . '/index.html', $swaggerUI);
    success("Created docs/index.html");

    // Simple list
    info("Generating validation list...");
    $list = $verifier->generateDocumentation('list');
    file_put_contents($docsDir . '/validations.txt', $list);
    success("Created docs/validations.txt");

    // JSON export
    info("Generating JSON export...");
    $json = $verifier->generateDocumentation('json');
    file_put_contents($docsDir . '/validations.json', $json);
    success("Created docs/validations.json");

    // Summary
    section("Documentation Generated Successfully");

    echo "\n📁 Output files:\n";
    echo "  • src/DataVerify.php    - PHPDoc updated\n";
    echo "  • docs/VALIDATIONS.md   - Full markdown documentation\n";
    echo "  • docs/index.html       - Interactive Swagger UI\n";
    echo "  • docs/openapi.json     - OpenAPI 3.1 schema\n";
    echo "  • docs/schema.json      - JSON Schema\n";
    echo "  • docs/validations.json - Complete JSON export\n";
    echo "  • docs/validations.txt  - Simple text list\n";

    echo "\n🌐 To view the interactive documentation:\n";
    echo "  1. Open docs/index.html in your browser\n";
    echo "  2. Or run: php -S localhost:8000 -t docs\n";
    echo "     Then visit: http://localhost:8000\n\n";
}

success("All tasks completed!");

// Usage info
if (in_array('--help', $argv) || in_array('-h', $argv)) {
    echo "\nUsage: php generate-docs.php [options]\n\n";
    echo "Options:\n";
    echo "  --phpdoc, -p      Update only PHPDoc in DataVerify.php\n";
    echo "  --skip-docs       Skip documentation generation\n";
    echo "  --help, -h        Show this help message\n\n";
    echo "Examples:\n";
    echo "  php generate-docs.php              # Generate everything\n";
    echo "  php generate-docs.php --phpdoc     # Update PHPDoc only\n";
    echo "  php generate-docs.php --skip-docs  # Update PHPDoc, skip docs\n\n";
}