<?php

use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;

class LengthTest extends TestCase
{
    public function testValidMinLength()
    {
        $data = new stdClass();
        $data->password = "secretpassword";
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("password")->required->minLength(8);
        
        $this->assertTrue($data_verifier->verify());
    }

    public function testInvalidMinLength()
    {
        $data = new stdClass();
        $data->password = "short";
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("password")->required->minLength(8);
        
        $this->assertFalse($data_verifier->verify());
        $errors = $data_verifier->getErrors();
        $this->assertEquals("The field password must be at least 8 characters", $errors[0]['message']);
    }

    public function testValidMaxLength()
    {
        $data = new stdClass();
        $data->username = "validuser";
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("username")->required->maxLength(10);
        
        $this->assertTrue($data_verifier->verify());
    }

    public function testInvalidMaxLength()
    {
        $data = new stdClass();
        $data->username = "averylongusername";
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("username")->required->maxLength(10);
        
        $this->assertFalse($data_verifier->verify());
        $errors = $data_verifier->getErrors();
        $this->assertEquals("The field username must not exceed 10 characters", $errors[0]['message']);
    }
}