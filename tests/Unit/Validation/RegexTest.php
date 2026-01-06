<?php

use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;

class RegexTest extends TestCase
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

    public function testRegexErrorMessageIsNotEmpty(): void
    {
        $data = new stdClass();
        $data->code = "INVALID";
        
        $pattern = '/^[A-Z]{3}-[0-9]{3}$/';
        
        $verifier = new DataVerify($data);
        $verifier->field('code')->regex($pattern);
        
        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        $message = $errors[0]['message'];
        
        $this->assertNotEmpty($message);
        $this->assertStringNotContainsString('{', $message);
        $this->assertStringNotContainsString('}', $message);
    }

    public function testRegexErrorMessageReferencesField(): void
    {
        $data = new stdClass();
        $data->postal_code = "invalid";
        
        $verifier = new DataVerify($data);
        $verifier->field('postal_code')->regex('/^[0-9]{5}$/');
        
        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        $message = $errors[0]['message'];
        
        $this->assertStringContainsString('postal_code', $message);
    }

    public function testRegexCustomErrorMessage(): void
    {
        $data = new stdClass();
        $data->license_plate = "INVALID";
        
        $pattern = '/^[A-Z]{2}-[0-9]{3}-[A-Z]{2}$/';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('license_plate')
            ->errorMessage('License plate must be in format: AB-123-CD')
            ->regex($pattern);
        
        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        
        $this->assertEquals(
            'License plate must be in format: AB-123-CD',
            $errors[0]['message']
        );
    }

    public function testRegexErrorMessageWithAlias(): void
    {
        $data = new stdClass();
        $data->ref = "INVALID";
        
        $pattern = '/^REF-[0-9]{4}$/';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('ref')
            ->alias('Reference Number')
            ->regex($pattern);
        
        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        $message = $errors[0]['message'];
        
        $this->assertStringContainsString('Reference Number', $message);
    }

    public function testMultipleRegexErrorsReferenceCorrectFields(): void
    {
        $data = new stdClass();
        $data->code1 = "INVALID1";
        $data->code2 = "INVALID2";
        $data->code3 = "INVALID3";
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('code1')->required->regex('/^[A-Z]{3}$/')
            ->field('code2')->required->regex('/^[0-9]{5}$/')
            ->field('code3')->required->regex('/^[A-Z][0-9]{2}$/');
        
        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        $this->assertCount(3, $errors);
        
        $this->assertStringContainsString('code1', $errors[0]['message']);
        $this->assertStringContainsString('code2', $errors[1]['message']);
        $this->assertStringContainsString('code3', $errors[2]['message']);
    }

    public function testRegexErrorMessageIsDescriptive(): void
    {
        $data = new stdClass();
        $data->value = "test";
        
        $verifier = new DataVerify($data);
        $verifier->field('value')->required->regex('/^[0-9]+$/');
        
        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        $message = $errors[0]['message'];
        
        $this->assertGreaterThan(15, strlen($message),
            "Message should be descriptive (>15 chars). Got: {$message}");
    }

    public function testRegexRejectsNonStringValues(): void
    {
        $verifier = new DataVerify((object)['test' => 123]);
        $verifier->field('test')->required->regex('/^[A-Z]+$/');
        $this->assertFalse($verifier->verify());
        
        $verifier2 = new DataVerify((object)['test' => []]);
        $verifier2->field('test')->required->regex('/^[A-Z]+$/');
        $this->assertFalse($verifier2->verify());
        
        $verifier3 = new DataVerify((object)['test' => null]);
        $verifier3->field('test')->required->regex('/^[A-Z]+$/');
        $this->assertFalse($verifier3->verify());
    }
}
