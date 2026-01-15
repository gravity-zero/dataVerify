<?php

use Gravity\DataVerify;
use Gravity\Schema\{Schema, SchemaBuilder};
use Gravity\Registry\{SchemaRegistry, RuleSetRegistry};
use PHPUnit\Framework\TestCase;

class SchemaTest extends TestCase
{
    protected function setUp(): void
    {
        SchemaRegistry::reset();
        RuleSetRegistry::reset();
    }

    public function testSchemaCreation(): void
    {
        $schema = new Schema('userApi');
        
        $this->assertEquals('userApi', $schema->getName());
        $this->assertEmpty($schema->getFields());
    }

    public function testSchemaRegistration(): void
    {
        DataVerify::registerSchema('userApi')
            ->field('user')->required->object
                ->subfield('email')->email;

        $registry = SchemaRegistry::instance();
        
        $this->assertTrue($registry->has('userApi'));
        $this->assertInstanceOf(Schema::class, $registry->get('userApi'));
    }

    public function testCannotRegisterSameSchemaTwice(): void
    {
        DataVerify::registerSchema('userApi')->field('user')->required;
        
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Schema 'userApi' is already registered");

        DataVerify::registerSchema('userApi')->field('user')->email;
    }

    public function testApplySchemaWithMethodSyntax(): void
    {
        DataVerify::registerSchema('userApi')
            ->field('user')->required->object
                ->subfield('email')->required->email
                ->subfield('password')->required->minLength(12);

        $data = ['user' => ['email' => 'invalid', 'password' => 'short']];
        
        $dv = new DataVerify($data);
        $dv->schema('userApi');
        
        $this->assertFalse($dv->verify());
        $errors = $dv->getErrors();
        $this->assertGreaterThanOrEqual(2, count($errors));
    }

    public function testApplySchemaWithPropertySyntax(): void
    {
        DataVerify::registerSchema('userApi')
            ->field('user')->required->object
                ->subfield('email')->required->email;

        $data = ['user' => ['email' => 'invalid']];
        
        $dv = new DataVerify($data);
        $dv->schema->userApi;
        
        $this->assertFalse($dv->verify());
    }

    public function testCannotApplyMultipleSchemas(): void
    {
        DataVerify::registerSchema('schema1')->field('a')->required;
        DataVerify::registerSchema('schema2')->field('b')->required;

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Cannot apply schema 'schema2'");

        $data = ['a' => 'test', 'b' => 'test'];
        
        $dv = new DataVerify($data);
        $dv->schema('schema1');
        $dv->schema('schema2');
    }

    public function testCannotApplySchemaAfterDefiningFields(): void
    {
        DataVerify::registerSchema('userApi')->field('user')->required;

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Cannot apply schema 'userApi'");

        $data = ['email' => 'test@example.com', 'user' => 'test'];
        
        $dv = new DataVerify($data);
        $dv->field('email')->required;
        $dv->schema('userApi');
    }

    public function testSchemaNotFound(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Schema 'nonexistent' not found");

        $data = ['test' => 'value'];
        
        $dv = new DataVerify($data);
        $dv->schema('nonexistent');
    }

    public function testSchemaWithConditionals(): void
    {
        DataVerify::registerSchema('userPatch')
            ->field('user')->required->object
                ->subfield('email')
                    ->email
                    ->when('user.id', '!=', null)
                        ->then->required
                ->subfield('password')
                    ->minLength(12)
                    ->when('user.id', '!=', null)
                        ->then->required;
        
        // PATCH scenario - user.id exists, fields are required
        $data1 = (object)[
            'user' => (object)[
                'id' => 123,
                'email' => '',
                'password' => ''
            ]
        ];
        $dv1 = new DataVerify($data1);
        $dv1->schema('userPatch');
        
        $this->assertFalse($dv1->verify());
        $errors1 = $dv1->getErrors();
        $this->assertGreaterThanOrEqual(2, count($errors1));

        // POST scenario - user.id is null, fields are optional but still validated if present
        DataVerify::registerSchema('userPost')
            ->field('user')->required->object
                ->subfield('email')
                    ->email
                    ->when('user.id', '!=', null)
                        ->then->required
                ->subfield('password')
                    ->minLength(12)
                    ->when('user.id', '!=', null)
                        ->then->required;
        
        $data2 = (object)[
            'user' => (object)[
                'id' => null,
                'email' => 'valid@example.com',
                'password' => 'ValidPassword123'
            ]
        ];
        $dv2 = new DataVerify($data2);
        $dv2->schema('userPost');
        
        $this->assertTrue($dv2->verify());
    }

    public function testSchemaWithRules(): void
    {
        DataVerify::registerRules('strongPassword')
            ->minLength(12)
            ->containsUpper
            ->containsLower;

        DataVerify::registerSchema('userApi')
            ->field('user')->required->object
                ->subfield('password')->rule('strongPassword');

        $data = ['user' => ['password' => 'weak']];
        
        $dv = new DataVerify($data);
        $dv->schema('userApi');
        
        $this->assertFalse($dv->verify());
        $errors = $dv->getErrors();
        $this->assertGreaterThanOrEqual(2, count($errors));
    }

    public function testSchemaWithRulesAndConditionals(): void
    {
        DataVerify::registerRules('adminPassword')
            ->minLength(16)
            ->containsSpecialCharacter;

        DataVerify::registerSchema('userWithRoles')
            ->field('user')->required->object
                ->subfield('password')
                    ->minLength(8)
                    ->when('user.role', '=', 'admin')
                        ->then->rule('adminPassword');

        // Regular user - only minLength(8)
        $data1 = (object)[
            'user' => (object)[
                'role' => 'user',
                'password' => 'Pass1234'
            ]
        ];
        $dv1 = new DataVerify($data1);
        $dv1->schema('userWithRoles');
        $this->assertTrue($dv1->verify());

        // Admin user - minLength(8) + adminPassword rules
        $data2 = (object)[
            'user' => (object)[
                'role' => 'admin',
                'password' => 'Pass1234'
            ]
        ];
        $dv2 = new DataVerify($data2);
        $dv2->schema('userWithRoles');
        $this->assertFalse($dv2->verify());
    }

    public function testRuleAndSchemaWithSameName(): void
    {
        // Should not conflict - different namespaces
        DataVerify::registerRules('user')->minLength(3);
        DataVerify::registerSchema('user')->field('name')->required;

        $data = ['name' => 'test', 'username' => 'ab'];
        
        $dv = new DataVerify($data);
        $dv->schema('user');
        $dv->field('username')->rule('user');
        
        $this->assertFalse($dv->verify());
    }
}