<?php
use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;
use Gravity\Exceptions\ValidationTestNotFoundException;

class ValidationTestNotFoundTest extends TestCase
{
    public function testFieldWithInvalidTestThrowsException()
    {
        $this->expectException(ValidationTestNotFoundException::class);
        $this->expectExceptionMessage("Validation test 'fakeTest' not found.");
        
        $data = new stdClass();
        $data->email = "test@example.com";
        $data_verifier = new DataVerify($data);
        
        $data_verifier->field("email")->fakeTest;
    }

    public function testSubfieldWithInvalidTestThrowsException()
    {
        $this->expectException(ValidationTestNotFoundException::class);
        $this->expectExceptionMessage("Validation test 'fakeSubTest' not found.");

        $data = new stdClass();
        $data->passwords = new stdClass();
        $data->passwords->field1 = new stdClass();
        $data->passwords->field1->field2 = "Some text";

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("passwords")->object
                ->subfield("field1", "field2")->fakeSubTest;

        //$data_verifier->verify();
    }

    public function testFieldWithInvalidTestThrowsExceptionManually()
    {
        $data = new stdClass();
        $data->email = "test@example.com";
        
        $this->expectException(ValidationTestNotFoundException::class);
            $this->expectExceptionMessage("Validation test 'fakeTest' not found.");
        
        $data_verifier = new DataVerify($data);
        $data_verifier->field("email")->fakeTest;
    }

    public function testFieldWithEmptyTestNameThrowsException()
    {
        $this->expectException(ValidationTestNotFoundException::class);
        $this->expectExceptionMessage("Validation test ' ' not found.");

        $data = new \stdClass();
        $data->email = "test@example.com";

        $data_verifier = new DataVerify($data);
        $data_verifier->field("email")->{" "}();

        $data_verifier->verify();
    }

    public function testValidationNotFoundExceptionHasZeroCode(): void
    {
        $exception = new ValidationTestNotFoundException('test');
        
        $this->assertEquals(0, $exception->getCode());
    }

    public function testValidationNotFoundExceptionAcceptsCustomCode(): void
    {
        $exception = new ValidationTestNotFoundException('test', 42);
        
        $this->assertEquals(42, $exception->getCode());
    }
}