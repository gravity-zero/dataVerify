<?php
use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;


class StringsTest extends TestCase
{
    public function testValidName()
    {
        $data = new stdClass();
        $data->name = "Doe";

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("name")->required->string->minLength(3);

        $this->assertTrue($data_verifier->verify());
    }

    public function testInvalidName()
    {
        $data = new stdClass();
        $data->name = 'ti';

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("name")->required->string->minLength(3);

        $this->assertFalse($data_verifier->verify());
        $errors = $data_verifier->getErrors();
        $this->assertEquals("The field name must be at least 3 characters", $errors[0]['message']);
    }
}