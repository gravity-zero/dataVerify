<?php
use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;


class IntegerTest extends TestCase
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

    public function testIntNonStrictRejectsInvalidStrings(): void
    {
        $verifier = new DataVerify((object)['value' => 'not-a-number']);
        $verifier->field('value')->required->int(strict: false);
        $this->assertFalse($verifier->verify());
        
        $verifier2 = new DataVerify((object)['value' => '12.5']);
        $verifier2->field('value')->required->int(strict: false);
        $this->assertFalse($verifier2->verify());
        
        $verifier3 = new DataVerify((object)['value' => '']);
        $verifier3->field('value')->required->int(strict: false);
        $this->assertFalse($verifier3->verify());
    }
}