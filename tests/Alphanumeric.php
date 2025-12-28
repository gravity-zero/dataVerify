<?php

use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;

class Alphanumeric extends TestCase
{
    public function testValidAlphanumeric()
    {
        $data = new stdClass();
        $data->username = "user123";
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("username")->required->alphanumeric;
        
        $this->assertTrue($data_verifier->verify());
    }

    public function testInvalidAlphanumeric()
    {
        $data = new stdClass();
        $data->username = "user@123";
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("username")->required->alphanumeric;
        
        $this->assertFalse($data_verifier->verify());
        $errors = $data_verifier->getErrors();
        $this->assertEquals("The field username must be alphanumeric", $errors[0]['message']);
    }

    public function testNotAlphanumeric()
    {
        $data = new stdClass();
        $data->symbol = "@#!$";
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("symbol")->required->notAlphanumeric;
        
        $this->assertTrue($data_verifier->verify());
    }

    public function testInvalidNotAlphanumeric()
    {
        $data = new stdClass();
        $data->symbol = "@#!é$";
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("symbol")->required->notAlphanumeric;
        
        $this->assertFalse($data_verifier->verify());
    }
}