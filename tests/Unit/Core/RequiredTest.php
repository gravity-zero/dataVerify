<?php

use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;

class RequiredTest extends TestCase
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

    public function testRequiredAtEndOfChainStillFailsOnMissingField(): void
    {
        $data = new stdClass();
        $verifier = new DataVerify($data);

        $verifier->field('email')->email->required;

        $this->assertFalse(
            $verifier->verify(),
            'Missing field must fail when required is at the end of the chain'
        );

        $errors = $verifier->getErrors();
        $this->assertEquals('The field email is required', $errors[0]['message']);
    }
}