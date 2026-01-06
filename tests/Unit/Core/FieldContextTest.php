<?php

use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;

class FieldContextTest extends TestCase
{
    public function testCallingValidationWithoutFieldThrows(): void
    {
        $data = new stdClass();
        $verifier = new DataVerify($data);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("field()");

        $verifier->required;
    }

    public function testMultipleFieldsAreValidated(): void
    {
        $data = new stdClass();
        $data->field1 = "";
        $data->field2 = "";
        $data->field3 = "";
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('field1')->required
            ->field('field2')->required
            ->field('field3')->required;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        
        $this->assertCount(3, $errors);
        
        $fields = array_column($errors, 'field');
        $this->assertContains('field1', $fields);
        $this->assertContains('field2', $fields);
        $this->assertContains('field3', $fields);
    }

    public function testFieldIsActuallyStoredInCollection(): void
    {
        $data = new stdClass();
        $data->field1 = 'value1';
        $data->field2 = 'value2';
        
        $verifier = new DataVerify($data);
        $verifier->field('field1')->required;
        $verifier->field('field2')->required;
        
        $reflection = new \ReflectionClass($verifier);
        $fieldsProperty = $reflection->getProperty('fields');
        $fields = $fieldsProperty->getValue($verifier);
        
        $this->assertCount(2, $fields);
    }

    public function testCompleteValidationChain(): void
    {
        $data = new stdClass();
        $data->user = new stdClass();
        $data->user->email = "bad";
        $data->user->age = 5;
        $data->email = "bad";
        $data->name = "";
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('user')->required->object
                ->subfield('email')->required->email
                ->subfield('age')->required->int->greaterThan(18)
            ->field('email')->required->email
            ->field('name')->required->string;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        
        $this->assertEquals(4, count($errors));
    }
}
