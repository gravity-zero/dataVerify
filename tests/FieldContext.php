<?php

use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;

class FieldContext extends TestCase
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
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('field1')->required
            ->field('field2')->required;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        $this->assertCount(2, $errors);
    }
}