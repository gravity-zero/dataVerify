<?php

use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;

class EmailTest extends TestCase
{
    public function testValidEmail()
    {
        $data = new stdClass();
        $data->email = "rot13@gmail.com";
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("email")->required->email;
        
        $this->assertTrue($data_verifier->verify());
    }

    public function testInvalidEmail()
    {
        $data = new stdClass();
        $data->email = "test.yyy.com";
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("email")->required->email;
        
        $this->assertFalse($data_verifier->verify());
    }

    public function testInvalidEmailType()
    {
        $data = new stdClass();
        $data->email = [0 => "myemail@gmail.com"];
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("email")->required->email;
        
        $this->assertFalse($data_verifier->verify());
    }

    public function testDefaultDisposableEmail()
    {
        $data = new stdClass();
        $data->email = "rot13@gmail.com";
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("email")->required->email->disposableEmail;
        
        $this->assertTrue($data_verifier->verify());
    }

    public function testInvalidDisposableEmail()
    {
        $data = new stdClass();
        $data->email = "test@yopmail.com";
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("email")->required->disposableEmail;
        
        $this->assertFalse($data_verifier->verify());
        $errors = $data_verifier->getErrors();
        $this->assertEquals("The field email cannot be a disposable email address", $errors[0]['message']);
    }

    public function testCustomDisposableEmail()
    {
        $data = new stdClass();
        $data->email = "rot13@gmail.com";
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("email")->required->email->disposableEmail(["@toto", "@test", "@666", "@dot"]);
        
        $this->assertTrue($data_verifier->verify());
    }

    public function testInvalidCustomDisposableEmail()
    {
        $data = new stdClass();
        $data->email = "rot13@dot.com";
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("email")->required->email->disposableEmail(["@toto", "@test", "@666", "@dot"]);
        
        $this->assertFalse($data_verifier->verify());
        $errors = $data_verifier->getErrors();
        $this->assertEquals("The field email cannot be a disposable email address", $errors[0]['message']);
    }
}