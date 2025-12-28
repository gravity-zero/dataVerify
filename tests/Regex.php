<?php

use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;

class Regex extends TestCase
{
    public function testRegexMatchAlphaNumeric(): void
    {
        $data = (object)["username" => "Hello123"];
        $data_verifier = new DataVerify($data);

        $data_verifier->field("username")
            ->regex("/^[A-Za-z0-9]+$/");

        $this->assertTrue($data_verifier->verify());
    }

    public function testRegexMatchLettersOnly(): void
    {
        $data = (object)["username" => "abcXYZ"];
        $data_verifier = new DataVerify($data);

        $data_verifier->field("username")
            ->regex("/^[A-Za-z]+$/");

        $this->assertTrue($data_verifier->verify());
    }

    public function testRegexInvalidPattern(): void
    {
        $data = (object)["username" => "AnyValue"];
        $data_verifier = new DataVerify($data);

        $data_verifier->field("username")
            ->regex("/unclosed[");

        $this->assertFalse($data_verifier->verify());
    }

    public function testRegexNoMatch(): void
    {
        $data = (object)["username" => "Hello"];
        $dv = new DataVerify($data);

        $dv->field("username")
            ->regex("/^world$/");

        $this->assertFalse($dv->verify());
    }
}
