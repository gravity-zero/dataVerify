<?php
use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;

class Contains extends TestCase
{

    public function testContainsSpecialCharacter()
    {
        $data = new stdClass();
        $data->password = "P@ssword123";

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("password")->required->containsSpecialCharacter;

        $this->assertTrue($data_verifier->verify());
    }

    public function testContainsLower()
    {
        $data = new stdClass();
        $data->password = "password123";

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("password")->required->containsLower;

        $this->assertTrue($data_verifier->verify());
    }

    public function testContainsUpper()
    {
        $data = new stdClass();
        $data->password = "Password123";

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("password")->required->containsUpper;

        $this->assertTrue($data_verifier->verify());
    }

    public function testContainsNumber()
    {
        $data = new stdClass();
        $data->password = "Password123";

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("password")->required->containsNumber;

        $this->assertTrue($data_verifier->verify());
    }
}