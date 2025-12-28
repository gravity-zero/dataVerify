<?php
use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;


class Numerics extends TestCase
{
    public function testValidNumeric()
    {
        $data = new stdClass();
        $data->age = 25;

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("age")->required->numeric->greaterThan(18)->lowerThan(100);

        $this->assertTrue($data_verifier->verify());
    }

    public function testInvalidNumeric()
    {
        $data = new stdClass();
        $data->age = 15;

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("age")->required->numeric->greaterThan(18)->lowerThan(100);

        $this->assertFalse($data_verifier->verify());
        $errors = $data_verifier->getErrors();
        $this->assertEquals("The field age must be greater than 18", $errors[0]['message']);
    }
}