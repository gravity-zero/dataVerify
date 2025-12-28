<?php

use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;

class Required extends TestCase
{
    public function testRequired()
    {
        $data = new stdClass();
        $data->email = "toto@gmail.com";
        $data_verifier = new DataVerify($data);
        $data_verifier->field("email")->required;
        $this->assertTrue($data_verifier->verify());
    }

    public function testInvalidRequired()
    {
        $data = new stdClass();
        $data_verifier = new DataVerify($data);
        $data_verifier->field("name")->required;
        
        $this->assertFalse($data_verifier->verify());
        $errors = $data_verifier->getErrors();
        $this->assertEquals("The field name is required", $errors[0]['message']);
    }

    public function testOptionalFieldSkipsValidationOnNull(): void {
        $data = new stdClass();
        $data->optional_email = null;
        
        $verifier = new DataVerify($data);
        $verifier->field('optional_email')->email;  // Pas de required
        
        $this->assertTrue($verifier->verify(), 
            'null should skip validation when field is not required');
    }

    public function testOptionalFieldValidatesWhenPresent(): void {
        $data = new stdClass();
        $data->optional_email = "invalid-email";
        
        $verifier = new DataVerify($data);
        $verifier->field('optional_email')->email;
        
        $this->assertFalse($verifier->verify(), 
            'Invalid value should fail validation even without required');
    }
}