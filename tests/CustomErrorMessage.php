<?php
use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;


class CustomErrorMessage extends TestCase
{
    public function testCustomErrorMessage()
    {
        $data = new stdClass();

        $data_verifier = new DataVerify($data);
        $data_verifier->field("name")->required->errorMessage("the field name is required, please do it!");

        $this->assertFalse($data_verifier->verify());

        $errors = $data_verifier->getErrors();

        $this->assertEquals("the field name is required, please do it!", $errors[0]['message']);
    }

    public function testMultipleCustomErrorMessage()
    {
        $data = new stdClass();

        $data_verifier = new DataVerify($data);
        $data_verifier->field("name")->required->errorMessage("the field name is required, please do it!");
        $data_verifier->field("birthday")->required->errorMessage("it's a birthday fictive date ?");
        $data_verifier->field("halloween")->required->errorMessage("Halloween mode not effective yet");


        $this->assertFalse($data_verifier->verify());

        $errors = $data_verifier->getErrors();

        $this->assertEquals("the field name is required, please do it!", $errors[0]['message']);
        $this->assertEquals("it's a birthday fictive date ?", $errors[1]['message']);
        $this->assertEquals("Halloween mode not effective yet", $errors[2]['message']);
    }
}