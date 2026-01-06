<?php

use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;

class AliasTest extends TestCase
{
    public function testAliasForField()
    {
        $data = new stdClass();
        $data->email5 = "totogmail.com";
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("email5")->required->email->alias("real_email");
        
        $this->assertFalse($data_verifier->verify());
        $errors = $data_verifier->getErrors();
        
        $this->assertEquals("The field real_email must be a valid email address", $errors[0]['message']);
    }

    public function testAliasWithSpace()
    {
        $data = new stdClass();
        $data->email5 = "totogmail.com";
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("email5")->required->email->alias("real email");
        
        $this->assertFalse($data_verifier->verify());
        $errors = $data_verifier->getErrors();
        
        $this->assertEquals("The field real email must be a valid email address", $errors[0]['message']);
    }

    public function testAliasDoesNotImpactPreviousFieldName()
    {
        $data = new stdClass();
        $data->email = "totogmail.com";
        $data->random_field = 222;
        $data->test = 2;
        $data->some_random_field = "dddddddddd";
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("email")->required->email
            ->field("random_field")->required->string->alias("specific_field")
            ->field("test")->required->int->greaterThan(3)
            ->field("some_random_field")->required->int->alias("another_specific_field");
        
        $is_valid = $data_verifier->verify();
        $this->assertFalse($is_valid);
        $errors = $data_verifier->getErrors();
        
        $this->assertEquals("The field email must be a valid email address", $errors[0]['message']);
        $this->assertEquals("The field specific_field must be a string", $errors[1]['message']);
        $this->assertEquals("The field test must be greater than 3", $errors[2]['message']);
        $this->assertEquals("The field another_specific_field must be an integer", $errors[3]['message']);
    }

    public function testAliasForSubfields()
    {
        $data = new \stdClass();
        $data->stuff = new \stdClass();
        $data->stuff->foo = new \stdClass();
        $data->stuff->foo->bar = 1234;
        $verifier = new DataVerify($data);
        $verifier
            ->field('stuff')->required->object
            ->subfield('foo', 'bar')
            ->required->string
            ->alias('My Subfield Alias');
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('My Subfield Alias', $errors[0]['message']);
    }
}