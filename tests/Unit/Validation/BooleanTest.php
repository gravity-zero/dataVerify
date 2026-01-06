<?php

use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;

class BooleanTest extends TestCase
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

    public function testBooleanErrorMessageIsDescriptive(): void
    {
        $data = new stdClass();
        $data->active = 1;
        
        $verifier = new DataVerify($data);
        $verifier->field('active')->boolean();
        
        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        $message = $errors[0]['message'];
        
        $this->assertStringContainsString('active', $message);
        
        $this->assertTrue(
            stripos($message, 'boolean') !== false || stripos($message, 'bool') !== false,
            "Error message should mention 'boolean' or 'bool'. Got: {$message}"
        );
    }

    /**
     * Test that error message is not a placeholder
     */
    public function testBooleanErrorMessageIsNotPlaceholder(): void
    {
        $data = new stdClass();
        $data->value = "invalid";
        
        $verifier = new DataVerify($data);
        $verifier->field('value')->boolean();
        
        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        $message = $errors[0]['message'];
        
        $this->assertNotEmpty($message);
        
        $this->assertStringNotContainsString('{', $message);
        $this->assertStringNotContainsString('}', $message);
        
        $this->assertGreaterThan(10, strlen($message));
    }

    public function testBooleanErrorMessageWithAlias(): void
    {
        $data = new stdClass();
        $data->is_active = 1;
        
        $verifier = new DataVerify($data);
        $verifier->field('is_active')->alias('Status Flag')->boolean();
        
        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        $message = $errors[0]['message'];
        
        $this->assertStringContainsString('Status Flag', $message);
    }

    public function testMultipleBooleanErrorsReferenceCorrectFields(): void
    {
        $data = new stdClass();
        $data->field1 = 1;
        $data->field2 = "invalid";
        $data->field3 = 2;
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('field1')->boolean(strict: true)
            ->field('field2')->boolean(strict: false)
            ->field('field3')->boolean(strict: false);
        
        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        $this->assertCount(3, $errors);
        
        $this->assertStringContainsString('field1', $errors[0]['message']);
        $this->assertStringContainsString('field2', $errors[1]['message']);
        $this->assertStringContainsString('field3', $errors[2]['message']);
    }

    public function testBooleanCustomErrorMessage(): void
    {
        $data = new stdClass();
        $data->accepted = 1;
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('accepted')
            ->errorMessage('You must explicitly accept the terms (true/false)')
            ->boolean();
        
        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        $message = $errors[0]['message'];
        
        $this->assertEquals(
            'You must explicitly accept the terms (true/false)',
            $message
        );
    }

    public function testBooleanStrictModeRejectsNonBooleanWithMessage(): void
    {
        $data = new stdClass();
        $data->flag = 1;
        
        $verifier = new DataVerify($data);
        $verifier->field('flag')->boolean(strict: true);
        
        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        
        $this->assertNotEmpty($errors[0]['message']);
        
        $this->assertStringContainsString('flag', $errors[0]['message']);
    }

    public function testBooleanLooseModeRejectsInvalidWithMessage(): void
    {
        $data = new stdClass();
        $data->value = "invalid_string";
        
        $verifier = new DataVerify($data);
        $verifier->field('value')->boolean(strict: false);
        
        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        
        $message = $errors[0]['message'];
        $this->assertNotEmpty($message);
        $this->assertStringNotContainsString('{', $message);
        $this->assertStringContainsString('value', $message);
    }
}