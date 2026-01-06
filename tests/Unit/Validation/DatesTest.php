<?php

use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;

class DatesTest extends TestCase
{
    public function testDate()
    {
        $data = new stdClass();
        $data->birthdate = "2000-01-01";
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("birthdate")->required->date;
        
        $this->assertTrue($data_verifier->verify());
    }

    public function testObjectDate()
    {
        $data = new stdClass();
        $data->birthdate = new DateTime("2000-01-01");
    
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("birthdate")->required->date;
    
        $this->assertTrue($data_verifier->verify());
    }
    
    public function testInvalidObjectDate()
    {
        $data = new stdClass();
        $data->birthdate = "invalid-date";
    
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("birthdate")->required->date;
    
        $this->assertFalse($data_verifier->verify());
        $errors = $data_verifier->getErrors();
        $this->assertEquals("The field birthdate must be a valid date", $errors[0]['message']);
    }

    public function testSpecificFormatDate()
    {
        $data = new stdClass();
        $data->birthdate = "01-01-2000";
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("birthdate")->required->date("d-m-Y");
        
        $this->assertTrue($data_verifier->verify());
    }

    public function testDateAcceptsInvalidDates(): void
    {
        $data = new stdClass();
        $data->date1 = "2025-02-30";
        $data->date2 = "2025-13-01";
        $data->date3 = "2025-04-31";
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('date1')->required->date
            ->field('date2')->required->date
            ->field('date3')->required->date;
        
        $result = $verifier->verify();
        
        $this->assertFalse($result, 'Invalid dates should fail validation');
        
        if (!$result) {
            $errors = $verifier->getErrors();
            $this->assertCount(3, $errors, 'All 3 invalid dates should generate errors');
        }
    }
    
    public function testDateAcceptsPartialFormats(): void
    {
        $data = new stdClass();
        $data->date = "2025-02";
        
        $verifier = new DataVerify($data);
        $verifier->field('date')->required->date('Y-m-d');
        
        $result = $verifier->verify();
        
        $this->assertFalse($result, 'Partial date format should fail for Y-m-d');
    }
    
    public function testDateAcceptsInvalidLeapYear(): void
    {
        $data = new stdClass();
        $data->date = "2023-02-29";
        
        $verifier = new DataVerify($data);
        $verifier->field('date')->required->date;
        
        $result = $verifier->verify();
        
        $this->assertFalse($result, '2023 is not a leap year, Feb 29 should fail');
    }
    
    public function testDateValidatesCorrectLeapYear(): void
    {
        $data = new stdClass();
        $data->date = "2024-02-29";
        
        $verifier = new DataVerify($data);
        $verifier->field('date')->required->date;
        
        $result = $verifier->verify();
        
        $this->assertTrue($result, '2024 is a leap year, Feb 29 should pass');
    }
}