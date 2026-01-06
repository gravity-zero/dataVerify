<?php
use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;


class ObjectsTest extends TestCase
{

    public function testObject(){
        $data = new stdClass();

        $data->data = new \stdClass();
        $data->data->field = "l";
        $data->data->second_field = "f";

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("data")->required->object;

        $this->assertTrue($data_verifier->verify());
    }

    public function testEmptyObject(){
        $data = new stdClass();

        $data->data = new \stdClass();

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("data")->object;

        $this->assertTrue($data_verifier->verify());
    }

    public function testRequiredEmptyObject(){
        $data = new stdClass();

        $data->data = new \stdClass();

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("data")->required->object;

        $this->assertFalse($data_verifier->verify());
    }

    public function testInvalidObject(){
        $data = new stdClass();

        $data->data = [new \stdClass()];

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("data")->object;

        $this->assertFalse($data_verifier->verify());
    }
}