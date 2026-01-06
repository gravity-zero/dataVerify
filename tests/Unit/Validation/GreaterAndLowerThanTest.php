<?php

use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;

class GreaterAndLowerThanTest extends TestCase
{
    public function testValidGreaterThan()
    {
        $data = new stdClass();
        $data->score = 15;
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("score")->required->numeric->greaterThan(10);
        
        $this->assertTrue($data_verifier->verify());
    }

    public function testInvalidGreaterThan()
    {
        $data = new stdClass();
        $data->score = 5;
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("score")->required->numeric->greaterThan(10);
        
        $this->assertFalse($data_verifier->verify());
        $errors = $data_verifier->getErrors();
        $this->assertEquals("The field score must be greater than 10", $errors[0]['message']);
    }

    public function testValidLowerThan()
    {
        $data = new stdClass();
        $data->score = 5;
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("score")->required->numeric->lowerThan(10);
        
        $this->assertTrue($data_verifier->verify());
    }

    public function testInvalidLowerThan()
    {
        $data = new stdClass();
        $data->score = 15;
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("score")->required->numeric->lowerThan(10);
        
        $this->assertFalse($data_verifier->verify());
        $errors = $data_verifier->getErrors();
        $this->assertEquals("The field score must be less than 10", $errors[0]['message']);
    }
}