<?php

use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;

class Json extends TestCase
{
    public function testJsonAcceptsValidJsonString()
    {
        $data = new stdClass();
        $data->config = '{"key": "value", "number": 123}';

        $verifier = new DataVerify($data);
        $verifier->field('config')->json();

        $this->assertTrue($verifier->verify());
    }

    public function testJsonAcceptsEmptyObject()
    {
        $data = new stdClass();
        $data->config = '{}';

        $verifier = new DataVerify($data);
        $verifier->field('config')->json();

        $this->assertTrue($verifier->verify());
    }

    public function testJsonAcceptsEmptyArray()
    {
        $data = new stdClass();
        $data->config = '[]';

        $verifier = new DataVerify($data);
        $verifier->field('config')->json();

        $this->assertTrue($verifier->verify());
    }

    public function testJsonAcceptsNestedStructures()
    {
        $data = new stdClass();
        $data->config = '{"user": {"name": "John", "preferences": {"theme": "dark"}}}';

        $verifier = new DataVerify($data);
        $verifier->field('config')->json();

        $this->assertTrue($verifier->verify());
    }

    public function testJsonAcceptsArrays()
    {
        $data = new stdClass();
        $data->config = '[1, 2, 3, "test", {"key": "value"}]';

        $verifier = new DataVerify($data);
        $verifier->field('config')->json();

        $this->assertTrue($verifier->verify());
    }

    public function testJsonRejectsInvalidSyntax()
    {
        $data = new stdClass();
        $data->config = '{"key": "value"'; // Missing closing brace

        $verifier = new DataVerify($data);
        $verifier->field('config')->json();

        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals('json', $errors[0]['test']);
    }

    public function testJsonRejectsPlainString()
    {
        $data = new stdClass();
        $data->config = 'not a json';

        $verifier = new DataVerify($data);
        $verifier->field('config')->json();

        $this->assertFalse($verifier->verify());
    }

    public function testJsonRejectsNonString()
    {
        $data = new stdClass();
        $data->config = 12345;

        $verifier = new DataVerify($data);
        $verifier->field('config')->json();

        $this->assertFalse($verifier->verify());
    }

    public function testJsonRejectsObject()
    {
        $data = new stdClass();
        $data->config = new stdClass();

        $verifier = new DataVerify($data);
        $verifier->field('config')->json();

        $this->assertFalse($verifier->verify());
    }

    public function testJsonRejectsArray()
    {
        $data = new stdClass();
        $data->config = ['key' => 'value'];

        $verifier = new DataVerify($data);
        $verifier->field('config')->json();

        $this->assertFalse($verifier->verify());
    }

    public function testJsonAcceptsStringifiedNumbers()
    {
        $data = new stdClass();
        $data->number = '123';
        $data->float = '12.34';

        $verifier = new DataVerify($data);
        $verifier
            ->field('number')->json()
            ->field('float')->json();

        $this->assertTrue($verifier->verify());
    }

    public function testJsonAcceptsStringifiedBooleans()
    {
        $data = new stdClass();
        $data->true_val = 'true';
        $data->false_val = 'false';

        $verifier = new DataVerify($data);
        $verifier
            ->field('true_val')->json()
            ->field('false_val')->json();

        $this->assertTrue($verifier->verify());
    }

    public function testJsonAcceptsNull()
    {
        $data = new stdClass();
        $data->null_val = 'null';

        $verifier = new DataVerify($data);
        $verifier->field('null_val')->json();

        $this->assertTrue($verifier->verify());
    }

    public function testJsonWithRequiredRejectsEmptyString()
    {
        $data = new stdClass();
        $data->config = '';

        $verifier = new DataVerify($data);
        $verifier->field('config')->required->json();

        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        $this->assertEquals('required', $errors[0]['test']);
    }
}