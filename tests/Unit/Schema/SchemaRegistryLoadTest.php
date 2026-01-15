<?php

use Gravity\DataVerify;
use Gravity\Interfaces\SchemaConfigInterface;
use Gravity\Schema\SchemaBuilder;
use Gravity\Registry\SchemaRegistry;
use PHPUnit\Framework\TestCase;

class SchemaRegistryLoadTest extends TestCase
{
    private string $fixturesPath;

    protected function setUp(): void
    {
        SchemaRegistry::reset();
        $this->fixturesPath = __DIR__ . '/../../fixtures/schemas';
    }

    protected function tearDown(): void
    {
        SchemaRegistry::reset();
    }

    public function testLoadFromDirectory(): void
    {
        $loaded = SchemaRegistry::instance()->loadFromDirectory(
            $this->fixturesPath,
            'Tests\\Fixtures\\Schemas'
        );

        // Should load UserSchema and ProductSchema
        // Should skip AbstractSchema and NotASchema
        $this->assertCount(2, $loaded);
        $this->assertContains('fixtureUser', $loaded);
        $this->assertContains('fixtureProduct', $loaded);

        // Verify schemas are actually registered
        $this->assertTrue(SchemaRegistry::instance()->has('fixtureUser'));
        $this->assertTrue(SchemaRegistry::instance()->has('fixtureProduct'));
    }

    public function testLoadFromDirectoryWithDataVerifyHelper(): void
    {
        $loaded = DataVerify::loadSchemasFrom(
            $this->fixturesPath,
            'Tests\\Fixtures\\Schemas'
        );

        $this->assertCount(2, $loaded);
        $this->assertTrue(SchemaRegistry::instance()->has('fixtureUser'));
        $this->assertTrue(SchemaRegistry::instance()->has('fixtureProduct'));
    }

    public function testLoadedSchemasAreUsable(): void
    {
        DataVerify::loadSchemasFrom(
            $this->fixturesPath,
            'Tests\\Fixtures\\Schemas'
        );

        // Test fixtureUser schema
        $data = [
            'email' => 'invalid-email',
            'name' => 'J',
            'age' => 25
        ];
        
        $dv = new DataVerify($data);
        $dv->schema('fixtureUser');
        
        $this->assertFalse($dv->verify());
        
        $errors = $dv->getErrors();
        $this->assertGreaterThanOrEqual(2, count($errors));
    }

    public function testLoadedSchemaWithValidData(): void
    {
        DataVerify::loadSchemasFrom(
            $this->fixturesPath,
            'Tests\\Fixtures\\Schemas'
        );

        $data = [
            'email' => 'valid@example.com',
            'name' => 'John Doe',
            'age' => 25
        ];
        
        $dv = new DataVerify($data);
        $dv->schema('fixtureUser');
        
        $this->assertTrue($dv->verify());
    }

    public function testLoadFromNonExistentDirectory(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Directory not found');

        SchemaRegistry::instance()->loadFromDirectory('/nonexistent/directory', 'Some\\Namespace');
    }

    public function testSkipsAbstractClasses(): void
    {
        $loaded = SchemaRegistry::instance()->loadFromDirectory(
            $this->fixturesPath,
            'Tests\\Fixtures\\Schemas'
        );

        // AbstractSchema should be skipped
        $this->assertNotContains('abstract', $loaded);
    }

    public function testSkipsClassesNotImplementingInterface(): void
    {
        $loaded = SchemaRegistry::instance()->loadFromDirectory(
            $this->fixturesPath,
            'Tests\\Fixtures\\Schemas'
        );

        // NotASchema should be skipped
        $this->assertNotContains('notASchema', $loaded);
        $this->assertFalse(SchemaRegistry::instance()->has('notASchema'));
    }

    public function testRegisterSingleSchemaConfig(): void
    {
        $config = new class implements SchemaConfigInterface {
            public function getName(): string
            {
                return 'inlineSchema';
            }

            public function define(SchemaBuilder $builder): void
            {
                $builder->field('test')->required->string;
            }
        };

        $name = SchemaRegistry::instance()->registerConfig($config);

        $this->assertEquals('inlineSchema', $name);
        $this->assertTrue(SchemaRegistry::instance()->has('inlineSchema'));
    }

    public function testRegisterMultipleSchemaConfigs(): void
    {
        $config1 = new class implements SchemaConfigInterface {
            public function getName(): string { return 'schema1'; }
            public function define(SchemaBuilder $builder): void {
                $builder->field('a')->required;
            }
        };

        $config2 = new class implements SchemaConfigInterface {
            public function getName(): string { return 'schema2'; }
            public function define(SchemaBuilder $builder): void {
                $builder->field('b')->required;
            }
        };

        $loaded = SchemaRegistry::instance()->registerConfigs([$config1, $config2]);

        $this->assertCount(2, $loaded);
        $this->assertContains('schema1', $loaded);
        $this->assertContains('schema2', $loaded);
    }

    public function testRegisterMultipleWithInvalidConfig(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must implement SchemaConfigInterface');

        SchemaRegistry::instance()->registerConfigs([new \stdClass()]);
    }

    public function testDoesNotRegisterDuplicateSchema(): void
    {
        $config = new class implements SchemaConfigInterface {
            public function getName(): string { return 'duplicate'; }
            public function define(SchemaBuilder $builder): void {
                $builder->field('test')->required;
            }
        };

        // Register first time
        $name1 = SchemaRegistry::instance()->registerConfig($config);
        $this->assertEquals('duplicate', $name1);

        // Try to register again - should return null
        $name2 = SchemaRegistry::instance()->registerConfig($config);
        $this->assertNull($name2);
    }

    public function testLoadFromEmptyDirectory(): void
    {
        $emptyDir = sys_get_temp_dir() . '/dataverify-empty-schemas-' . uniqid();
        mkdir($emptyDir);

        try {
            $loaded = SchemaRegistry::instance()->loadFromDirectory($emptyDir, 'Some\\Namespace');
            $this->assertEmpty($loaded);
        } finally {
            rmdir($emptyDir);
        }
    }

    public function testProductSchemaValidation(): void
    {
        DataVerify::loadSchemasFrom(
            $this->fixturesPath,
            'Tests\\Fixtures\\Schemas'
        );

        // Invalid product
        $data1 = [
            'title' => 'AB', // Too short
            'price' => 'not-a-number',
            'stock' => 10
        ];
        
        $dv1 = new DataVerify($data1);
        $dv1->schema('fixtureProduct');
        
        $this->assertFalse($dv1->verify());

        // Valid product
        SchemaRegistry::reset();
        DataVerify::loadSchemasFrom(
            $this->fixturesPath,
            'Tests\\Fixtures\\Schemas'
        );

        $data2 = [
            'title' => 'Widget',
            'price' => 29.99,
            'stock' => 100
        ];
        
        $dv2 = new DataVerify($data2);
        $dv2->schema('fixtureProduct');
        
        $this->assertTrue($dv2->verify());
    }
}