<?php

use Gravity\DataVerify;
use PHPUnit\Framework\TestCase;

class EdgeCasesRegressionTest extends TestCase
{
    public function testFalseBooleanShouldBeValid(): void
    {
        $data = new stdClass();
        $data->is_active = false;
        
        $verifier = new DataVerify($data);
        $verifier->field('is_active')->required->boolean;
        
        $this->assertTrue($verifier->verify());
    }

    public function testZeroIntegerShouldBeValid(): void
    {
        $data = new stdClass();
        $data->count = 0;
        
        $verifier = new DataVerify($data);
        $verifier->field('count')->required->int;
        
        $this->assertTrue($verifier->verify());
    }

    public function testZeroStringShouldBeValid(): void
    {
        $data = new stdClass();
        $data->value = "0";
        
        $verifier = new DataVerify($data);
        $verifier->field('value')->required->string;
        
        $this->assertTrue($verifier->verify());
    }

    public function testEmptyArrayShouldBeInvalid(): void
    {
        $data = new stdClass();
        $data->items = [];
        
        $verifier = new DataVerify($data);
        $verifier->field('items')->required->array;
        
        $this->assertFalse($verifier->verify());
    }

    public function testEmptyStringShouldBeInvalid(): void
    {
        $data = new stdClass();
        $data->name = "";
        
        $verifier = new DataVerify($data);
        $verifier->field('name')->required->string;
        
        $this->assertFalse($verifier->verify());
    }

    public function testNullWithoutRequiredShouldBeValid(): void
    {
        $data = new stdClass();
        $data->optional = null;
        
        $verifier = new DataVerify($data);
        $verifier->field('optional')->string;
        
        $this->assertTrue($verifier->verify());
    }

    public function testZeroFloatShouldBeValid(): void
    {
        $data = new stdClass();
        $data->price = 0.0;
        
        $verifier = new DataVerify($data);
        $verifier->field('price')->required->numeric;
        
        $this->assertTrue($verifier->verify());
    }

    public function testEmptyObjectShouldBeInvalid(): void
    {
        $data = new stdClass();
        $data->meta = new stdClass();
        
        $verifier = new DataVerify($data);
        $verifier->field('meta')->required->object;
        
        $this->assertFalse($verifier->verify());
    }

    public function testValidationIsActuallyRegistered(): void
    {
        $data = new stdClass();
        $data->email = "invalid-email";
        
        $verifier = new DataVerify($data);
        $verifier->field('email')->required->email->minLength(5);
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        
        $this->assertGreaterThan(0, count($errors));
    }

    public function testConditionalStateIsResetBetweenFields(): void
    {
        $data = (object)[
            'trigger1' => true,
            'trigger2' => false,
            'field1' => '',
            'field2' => ''
        ];
        
        $dv = new DataVerify($data);
        $dv->field('field1')
            ->when('trigger1', '=', true)
            ->then->required;
        
        $dv->field('field2')->required;
        
        $this->assertFalse($dv->verify());
        
        $errors = $dv->getErrors();
        $this->assertCount(2, $errors);
    }

    public function testAddValidationMustBeCalled(): void
    {
        $data = new stdClass();
        $data->email = 'invalid';
        
        $v = new DataVerify($data);
        $v->field('email')->email;
        
        $this->assertFalse($v->verify());
    }
}
