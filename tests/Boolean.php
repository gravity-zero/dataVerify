<?php

use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;

class Boolean extends TestCase
{
    public function testBooleanStrictModeAcceptsOnlyBooleans()
    {
        $data = new stdClass();
        $data->active = true;
        $data->inactive = false;

        $verifier = new DataVerify($data);
        $verifier
            ->field('active')->boolean()
            ->field('inactive')->boolean();

        $this->assertTrue($verifier->verify());
    }

    public function testBooleanStrictModeRejectsIntegers()
    {
        $data = new stdClass();
        $data->active = 1;

        $verifier = new DataVerify($data);
        $verifier->field('active')->boolean();

        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals('boolean', $errors[0]['test']);
    }

    public function testBooleanStrictModeRejectsStrings()
    {
        $data = new stdClass();
        $data->active = "1";

        $verifier = new DataVerify($data);
        $verifier->field('active')->boolean();

        $this->assertFalse($verifier->verify());
    }

    public function testBooleanLooseModeAcceptsBooleans()
    {
        $data = new stdClass();
        $data->active = true;
        $data->inactive = false;

        $verifier = new DataVerify($data);
        $verifier
            ->field('active')->boolean(strict: false)
            ->field('inactive')->boolean(strict: false);

        $this->assertTrue($verifier->verify());
    }

    public function testBooleanLooseModeAcceptsIntegers()
    {
        $data = new stdClass();
        $data->yes = 1;
        $data->no = 0;

        $verifier = new DataVerify($data);
        $verifier
            ->field('yes')->boolean(strict: false)
            ->field('no')->boolean(strict: false);

        $this->assertTrue($verifier->verify());
    }

    public function testBooleanLooseModeAcceptsStringBooleans()
    {
        $data = new stdClass();
        $data->yes = "1";
        $data->no = "0";

        $verifier = new DataVerify($data);
        $verifier
            ->field('yes')->boolean(strict: false)
            ->field('no')->boolean(strict: false);

        $this->assertTrue($verifier->verify());
    }

    public function testBooleanLooseModeRejectsInvalidValues()
    {
        $data = new stdClass();
        $data->invalid1 = 2;
        $data->invalid2 = "yes";
        $data->invalid3 = null;

        $verifier = new DataVerify($data);
        $verifier
            ->field('invalid1')->required->boolean(strict: false)
            ->field('invalid2')->required->boolean(strict: false)
            ->field('invalid3')->required->boolean(strict: false);

        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        $this->assertCount(3, $errors);
    }

    public function testBooleanRejectsNull()
    {
        $data = new stdClass();
        $data->value = null;

        $verifier = new DataVerify($data);
        $verifier->field('value')->required->boolean(strict: false);

        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals('required', $errors[0]['test']);
    }
}