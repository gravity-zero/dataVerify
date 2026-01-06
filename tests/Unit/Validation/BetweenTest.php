<?php

use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;

class BetweenTest extends TestCase
{
    public function testBetweenDate()
    {
        $data = new stdClass();
        $data->date = new DateTime("2000-01-01");

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("date")->required->date
            ->between(new DateTime("1920-01-01"), new DateTime("2020-01-01"));

        $this->assertTrue($data_verifier->verify());
    }

    public function testInvalidBetweenDate()
    {
        $data = new stdClass();
        $data->date = new DateTime("1910-01-01");

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("date")->required->date->between(new DateTime("1920-01-01"), new DateTime("2020-01-01"));

        $this->assertFalse($data_verifier->verify());
        $errors = $data_verifier->getErrors();
        $this->assertEquals("The field date must be between 1920-01-01 and 2020-01-01", $errors[0]['message']);
    }

    public function testBetweenInteger()
    {
        $data = new stdClass();

        $data->string_age = "5";
        $data->positive_age = 5;
        $data->negative_age = -5;
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("string_age")->required->int(false)->between(2, "8")
            ->field("positive_age")->required->int->between(2, 8)
            ->field("negative_age")->required->int->between(-7, 8);

        $is_valid = $data_verifier->verify();
        $this->assertTrue($is_valid);
    }

    public function testInvalidBetweenInteger()
    {
        $data = new stdClass();
        $data->string_age = "49";
        $data->positive_age = 49;
        $data->negative_age = -5;
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("string_age")->required->int(false)->between("1", "45")
            ->field("positive_age")->required->int->between(2, 8)
            ->field("negative_age")->required->int->between(-2, 8);

        $this->assertFalse($data_verifier->verify());
        $errors = $data_verifier->getErrors();

        $this->assertEquals("The field string_age must be between 1 and 45", $errors[0]['message']);
        $this->assertEquals("The field positive_age must be between 2 and 8", $errors[1]['message']);
        $this->assertEquals("The field negative_age must be between -2 and 8", $errors[2]['message']);
    }

    public function testBetweenNumerics()
    {
        $data = new stdClass();
        $data->string_amount = "5.5";
        $data->positive_amount = 5.5;
        $data->negative_amount = -5.5;

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("string_amount")->required->numeric->between(1, 10.5)
            ->field("positive_amount")->required->numeric->between(1, 10.5)
            ->field("negative_amount")->required->numeric->between(-100, 10.5);

        $is_valid = $data_verifier->verify();
        $this->assertTrue($is_valid);
    }

    public function testInvalidBetweenNumerics()
    {
        $data = new stdClass();
        $data->string_amount = "15.5";
        $data->positive_amount = 15.5;
        $data->negative_amount = -50.5;

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("string_amount")->required->numeric->between(1, 10.5)
            ->field("positive_amount")->required->numeric->between(1, 10.5)
            ->field("negative_amount")->required->numeric->between(-50, 10.5);

        $this->assertFalse($data_verifier->verify());

        $errors = $data_verifier->getErrors();

        $this->assertSame("The field string_amount must be between 1 and 10.5", $errors[0]['message']);
        $this->assertSame("The field positive_amount must be between 1 and 10.5", $errors[1]['message']);
        $this->assertSame("The field negative_amount must be between -50 and 10.5", $errors[2]['message']);
    }

    public function testBetweenRejectsIncompatibleTypes(): void
    {
        $data = new stdClass();
        $data->date = "2025-12-01";
        
        $verifier = new DataVerify($data);
        $verifier->field('date')->required->string->between(new DateTime("2025-02-01"), new DateTime("2025-03-01"));
        
        $result = $verifier->verify();
        
        $this->assertFalse($result, 'String vs DateTime should be rejected');
    }
    
    public function testBetweenRejectsNonNumericStrings(): void
    {
        $data = new stdClass();
        $data->value1 = "abc";
        
        $verifier = new DataVerify($data);
        $verifier->field('value1')->required->string->between(1, 100);
        
        $result = $verifier->verify();
        
        $this->assertFalse($result, 'Non-numeric string vs int should be rejected');
    }
    
    public function testBetweenRejectsDateStrings(): void
    {
        $data = new stdClass();
        $data->date = "2025-01-15";
        
        $verifier = new DataVerify($data);
        $verifier->field('date')->required->string->between("2025-01-01", "2025-01-31");
        
        $result = $verifier->verify();
        
        $this->assertFalse($result, 'Date strings should not use lexicographic comparison');
    }
    
    public function testBetweenHandlesDateTimeComparison(): void
    {
        $data = new stdClass();
        $data->date = new \DateTime("2025-06-15");
        
        $verifier = new DataVerify($data);
        $verifier->field('date')->required->date->between(
            new \DateTime("2025-01-01"),
            new \DateTime("2025-12-31")
        );
        
        $result = $verifier->verify();
        
        $this->assertTrue($result, 'DateTime objects should be comparable');
        
        $data2 = new stdClass();
        $data2->date = new \DateTime("2026-06-15");
        
        $verifier2 = new DataVerify($data2);
        $verifier2->field('date')->required->date->between(
            new \DateTime("2025-01-01"),
            new \DateTime("2025-12-31")
        );
        
        $result2 = $verifier2->verify();
        
        $this->assertFalse($result2, 'DateTime outside range should fail');
    }
    
    public function testBetweenRejectsDateTimeVsString(): void
    {
        $data = new stdClass();
        $data->date = new \DateTime("2025-06-15");
        
        $verifier = new DataVerify($data);
        $verifier->field('date')->required->date->between("2025-01-01", "2025-12-31");
        
        $result = $verifier->verify();
        
        $this->assertFalse($result, 'DateTime vs string should be rejected');
    }
    
    public function testBetweenHandlesNumericStrings(): void
    {
        $data = new stdClass();
        $data->zip = "75001";
        
        $verifier = new DataVerify($data);
        $verifier->field('zip')->required->numeric->between("01000", "99000");
        
        $result = $verifier->verify();
        
        $this->assertTrue($result, 'Numeric strings should be normalized and compared');
    }
}
