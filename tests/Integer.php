<?php
use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;


class Integer extends TestCase
{

    public function testInt()
    {
        $data = new stdClass();
        $data->age = 25;

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("age")->required->int;

        $this->assertTrue($data_verifier->verify());
    }

    public function testStringInt()
    {
        $data = new stdClass();
        $data->age = "25";

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("age")->required->int(false);

        $this->assertTrue($data_verifier->verify());
    }

    public function testInvalidInt()
    {
        $data = new stdClass();
        $data->age = 't45';

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("age")->required->int;

        $this->assertFalse($data_verifier->verify());
    }
}