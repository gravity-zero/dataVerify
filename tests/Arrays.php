<?php

use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;

class Arrays extends TestCase
{
    public function testArray()
    {
        $data = new stdClass();
        $data->data = ["first", "test"];
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("data")->required->array;
        
        $this->assertTrue($data_verifier->verify());
    }

    public function testEmptyArray()
    {
        $data = new stdClass();
        $data->data = [];
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("data")->array;
        
        $this->assertTrue($data_verifier->verify());
    }

    public function testRequiredEmptyArray()
    {
        $data = new stdClass();
        $data->data = [];
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("data")->required->array;
        
        $this->assertFalse($data_verifier->verify());
    }

    public function testInvalidArray()
    {
        $data = new stdClass();
        $data->data = new \stdClass();
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("data")->array;
        
        $this->assertFalse($data_verifier->verify());
        $errors = $data_verifier->getErrors();
        $this->assertEquals("The field data must be an array", $errors[0]['message']);
    }

    public function testArrayWithMixedTypes()
    {
        $data = new stdClass();
        $data->items = [1, "string", true, null, ["nested"]];
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("items")->required->array;
        
        $this->assertTrue($data_verifier->verify());
    }

    public function testArrayNotRequiredMissing()
    {
        $data = new stdClass();
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("optional_array")->array;
        
        $this->assertTrue($data_verifier->verify());
    }

    public function testInvalidArrayTypes()
    {
        $testCases = [
            'string' => "not an array",
            'integer' => 123,
            'float' => 12.34,
            'boolean' => true,
            'null' => null
        ];

        foreach ($testCases as $type => $value) {
            $data = new stdClass();
            $data->field = $value;
            $data_verifier = new DataVerify($data);
            $data_verifier->field("field")->required->array;
            
            $this->assertFalse(
                $data_verifier->verify(),
                "Array validation should fail for type: {$type}"
            );
        }
    }
}