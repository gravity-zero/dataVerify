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

    public function testJsonErrorMessageIsDescriptive(): void
    {
        $data = new stdClass();
        $data->config = "not a json string";
        
        $verifier = new DataVerify($data);
        $verifier->field('config')->json();
        
        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        $message = $errors[0]['message'];
        
        $this->assertTrue(
            stripos($message, 'json') !== false,
            "Error message should mention 'JSON'. Got: {$message}"
        );
        
        $this->assertStringContainsString('config', $message);
    }

    public function testInvalidJsonSyntaxErrorMessage(): void
    {
        $data = new stdClass();
        $data->data = '{"invalid": json}';
        
        $verifier = new DataVerify($data);
        $verifier->field('data')->json();
        
        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        $message = $errors[0]['message'];
        
        $this->assertNotEmpty($message);
        $this->assertStringContainsString('data', $message);
    }

    public function testJsonErrorWithAliasShowsAlias(): void
    {
        $data = new stdClass();
        $data->api_response = "invalid";
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('api_response')
            ->alias('API Response')
            ->json();
        
        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        $message = $errors[0]['message'];
        
        $this->assertStringContainsString('API Response', $message);
    }

    public function testJsonCustomErrorMessage(): void
    {
        $data = new stdClass();
        $data->settings = "not json";
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('settings')
            ->errorMessage('Settings must be valid JSON format')
            ->json();
        
        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        
        $this->assertEquals(
            'Settings must be valid JSON format',
            $errors[0]['message']
        );
    }

    public function testMultipleJsonErrorsHaveDistinctMessages(): void
    {
        $data = new stdClass();
        $data->config = "invalid";
        $data->settings = "also invalid";
        $data->metadata = "not json either";
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('config')->json()
            ->field('settings')->json()
            ->field('metadata')->json();
        
        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        $this->assertCount(3, $errors);
        
        $this->assertStringContainsString('config', $errors[0]['message']);
        $this->assertStringContainsString('settings', $errors[1]['message']);
        $this->assertStringContainsString('metadata', $errors[2]['message']);
    }

    public function testJsonErrorMessageIsNotEmptyOrPlaceholder(): void
    {
        $data = new stdClass();
        $data->value = "not json";
        
        $verifier = new DataVerify($data);
        $verifier->field('value')->json();
        
        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        $message = $errors[0]['message'];
        
        $this->assertNotEmpty($message);
        $this->assertGreaterThan(10, strlen($message));
        $this->assertStringNotContainsString('{', $message);
        $this->assertStringNotContainsString('}', $message);
    }

    public function testJsonValidationOnNestedFieldShowsPath(): void
    {
        $data = new stdClass();
        $data->api = new stdClass();
        $data->api->response = "invalid json";
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('api')->object
                ->subfield('response')->json();
        
        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        $message = $errors[0]['message'];
        
        $this->assertTrue(
            stripos($message, 'api.response') !== false || 
            stripos($message, 'response') !== false,
            "Message should reference the field. Got: {$message}"
        );
    }

    public function testJsonErrorMentionsFormatOrValidity(): void
    {
        $data = new stdClass();
        $data->data = "{broken";
        
        $verifier = new DataVerify($data);
        $verifier->field('data')->json();
        
        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        $message = $errors[0]['message'];
        
        $this->assertTrue(
            stripos($message, 'valid') !== false ||
            stripos($message, 'format') !== false ||
            stripos($message, 'json') !== false,
            "Message should mention valid/format/json. Got: {$message}"
        );
    }
}