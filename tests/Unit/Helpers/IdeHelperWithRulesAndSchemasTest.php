<?php

use Gravity\DataVerify;
use Gravity\Documentation\{IdeHelperManager, GeneratePHPDoc};
use Gravity\Registry\{RuleSetRegistry, SchemaRegistry, GlobalStrategyRegistry};
use Gravity\Validations\ValidationStrategy;
use PHPUnit\Framework\TestCase;

class IdeHelperWithRulesAndSchemasTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        RuleSetRegistry::reset();
        SchemaRegistry::reset();
        GlobalStrategyRegistry::reset();
        IdeHelperManager::reset();
        
        $this->tempFile = sys_get_temp_dir() . '/test-ide-helper-' . uniqid() . '.php';
    }

    protected function tearDown(): void
    {
        RuleSetRegistry::reset();
        SchemaRegistry::reset();
        GlobalStrategyRegistry::reset();
        IdeHelperManager::reset();
        
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testGenerateRuleAnnotations(): void
    {
        DataVerify::registerRules('strongPassword')
            ->minLength(12)
            ->containsUpper
            ->containsLower;

        DataVerify::registerRules('emailFormat')
            ->required
            ->email;

        $annotations = GeneratePHPDoc::generateRuleAnnotations();

        $this->assertArrayHasKey('properties', $annotations);
        $this->assertArrayHasKey('methods', $annotations);
        $this->assertCount(2, $annotations['properties']);
        $this->assertCount(2, $annotations['methods']);

        // Check content
        $propertiesStr = implode("\n", $annotations['properties']);
        $this->assertStringContainsString('strongPassword', $propertiesStr);
        $this->assertStringContainsString('emailFormat', $propertiesStr);
        $this->assertStringContainsString('minLength', $propertiesStr);
        $this->assertStringContainsString('containsUpper', $propertiesStr);
    }

    public function testGenerateSchemaAnnotations(): void
    {
        DataVerify::registerSchema('userApi')
            ->field('email')->required->email
            ->field('name')->required->string;

        DataVerify::registerSchema('productApi')
            ->field('title')->required
            ->field('price')->required->numeric;

        $annotations = GeneratePHPDoc::generateSchemaAnnotations();

        $this->assertArrayHasKey('properties', $annotations);
        $this->assertArrayHasKey('methods', $annotations);
        $this->assertCount(2, $annotations['properties']);
        $this->assertCount(2, $annotations['methods']);

        // Check content
        $propertiesStr = implode("\n", $annotations['properties']);
        $this->assertStringContainsString('userApi', $propertiesStr);
        $this->assertStringContainsString('productApi', $propertiesStr);
        $this->assertStringContainsString('email', $propertiesStr);
        $this->assertStringContainsString('title', $propertiesStr);
    }

    public function testGenerateCompleteIdeHelper(): void
    {
        // Register a custom strategy
        $customStrategy = new class extends ValidationStrategy {
            public function getName(): string
            {
                return 'customTest';
            }

            protected function handler(mixed $value, int $threshold = 10): bool
            {
                return is_numeric($value) && $value > $threshold;
            }
        };

        // Register rules
        DataVerify::registerRules('strongPassword')
            ->minLength(12)
            ->containsUpper;

        // Register schema
        DataVerify::registerSchema('userApi')
            ->field('email')->required->email;

        $content = GeneratePHPDoc::generateCompleteIdeHelper([$customStrategy]);

        // Check DataVerify namespace with custom strategy
        $this->assertStringContainsString('namespace Gravity {', $content);
        $this->assertStringContainsString('customTest', $content);
        $this->assertStringContainsString('$threshold', $content);

        // Check RuleProxy annotations
        $this->assertStringContainsString('class RuleProxy', $content);
        $this->assertStringContainsString('strongPassword', $content);
        $this->assertStringContainsString('minLength, containsUpper', $content);

        // Check SchemaProxy annotations
        $this->assertStringContainsString('class SchemaProxy', $content);
        $this->assertStringContainsString('userApi', $content);
        $this->assertStringContainsString('Schema with fields: email', $content);
    }

    public function testIdeHelperManagerNotifiesOnRuleRegistration(): void
    {
        $manager = IdeHelperManager::instance();
        $manager->enable($this->tempFile);
        $manager->disableDebounce();

        // Register a rule - should trigger regeneration
        DataVerify::registerRules('testRule')
            ->required
            ->string;

        $this->assertFileExists($this->tempFile);
        
        $content = file_get_contents($this->tempFile);
        $this->assertStringContainsString('testRule', $content);
        $this->assertStringContainsString('RuleProxy', $content);
    }

    public function testIdeHelperManagerNotifiesOnSchemaRegistration(): void
    {
        $manager = IdeHelperManager::instance();
        $manager->enable($this->tempFile);
        $manager->disableDebounce();

        // Register a schema - should trigger regeneration
        DataVerify::registerSchema('testSchema')
            ->field('name')->required;

        $this->assertFileExists($this->tempFile);
        
        $content = file_get_contents($this->tempFile);
        $this->assertStringContainsString('testSchema', $content);
        $this->assertStringContainsString('SchemaProxy', $content);
    }

    public function testIdeHelperCombinesStrategiesRulesAndSchemas(): void
    {
        $manager = IdeHelperManager::instance();
        $manager->enable($this->tempFile);
        $manager->disableDebounce();

        // Register custom strategy
        $strategy = new class extends ValidationStrategy {
            public function getName(): string
            {
                return 'myCustomValidation';
            }

            protected function handler(mixed $value): bool
            {
                return true;
            }
        };
        DataVerify::global()->register($strategy);
        $manager->registerStrategy($strategy);

        // Register rule
        DataVerify::registerRules('myRule')->required;

        // Register schema
        DataVerify::registerSchema('mySchema')->field('test')->required;

        $content = file_get_contents($this->tempFile);

        // All three should be present
        $this->assertStringContainsString('myCustomValidation', $content);
        $this->assertStringContainsString('myRule', $content);
        $this->assertStringContainsString('mySchema', $content);
    }

    public function testIdeHelperDoesNotGenerateWhenDisabled(): void
    {
        $manager = IdeHelperManager::instance();
        // Don't enable

        DataVerify::registerRules('testRule')->required;
        DataVerify::registerSchema('testSchema')->field('name')->required;

        $this->assertFileDoesNotExist($this->tempFile);
    }

    public function testEmptyRulesAndSchemasGenerateValidFile(): void
    {
        $content = GeneratePHPDoc::generateCompleteIdeHelper([]);

        $this->assertStringContainsString('namespace Gravity {', $content);
        $this->assertStringContainsString('class DataVerify', $content);
        $this->assertStringContainsString('class RuleProxy', $content);
        $this->assertStringContainsString('class SchemaProxy', $content);
    }

    public function testRuleAnnotationsShowValidationDetails(): void
    {
        DataVerify::registerRules('passwordPolicy')
            ->minLength(8)
            ->maxLength(100)
            ->containsUpper
            ->containsLower
            ->containsNumber
            ->containsSpecialCharacter;

        $annotations = GeneratePHPDoc::generateRuleAnnotations();
        $propertiesStr = implode("\n", $annotations['properties']);

        // Should list all validations
        $this->assertStringContainsString('minLength', $propertiesStr);
        $this->assertStringContainsString('maxLength', $propertiesStr);
        $this->assertStringContainsString('containsUpper', $propertiesStr);
        $this->assertStringContainsString('containsLower', $propertiesStr);
        $this->assertStringContainsString('containsNumber', $propertiesStr);
        $this->assertStringContainsString('containsSpecialCharacter', $propertiesStr);
    }

    public function testSchemaAnnotationsShowFieldNames(): void
    {
        DataVerify::registerSchema('complexSchema')
            ->field('user')->required->object
                ->subfield('email')->required->email
                ->subfield('profile')->required->object
            ->field('metadata')->array;

        $annotations = GeneratePHPDoc::generateSchemaAnnotations();
        $propertiesStr = implode("\n", $annotations['properties']);

        // Should show top-level fields
        $this->assertStringContainsString('user', $propertiesStr);
        $this->assertStringContainsString('metadata', $propertiesStr);
    }

    public function testIdeHelperManagerReset(): void
    {
        $manager1 = IdeHelperManager::instance();
        $manager1->enable($this->tempFile);

        IdeHelperManager::reset();

        $manager2 = IdeHelperManager::instance();
        
        $this->assertFalse($manager2->isEnabled());
        $this->assertNotSame($manager1, $manager2);
    }
}