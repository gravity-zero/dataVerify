<?php

use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;

class Subfields extends TestCase
{
    public function testSubfieldWithoutFieldThrows(): void
    {
        $data = new stdClass();
        $verifier = new DataVerify($data);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("field()");

        $verifier->subfield("a");
    }

    public function testArraySubfieldsValid()
    {
        $data = new stdClass();
        $data->passwords = [];
        $data->passwords['field1'] = [];
        $data->passwords['field1']['field2'] = "Some text";
        $data->passwords['field3'] = "Another text";
        $data->passwords['field4'] = new stdClass();
        $data->passwords['field4']->field5 = "Object field text";

        $verifier = new DataVerify($data);
        $verifier
            ->field("passwords")->required->array
            ->subfield("field1", "field2")->required->string
            ->subfield("field3")->required->string
            ->subfield("field4", "field5")->required->string;

        $this->assertTrue($verifier->verify());
    }

    public function testArraySubfieldsInvalid_MissingSubfield()
    {
        $data = new stdClass();
        $data->passwords = [];
        $data->passwords['field1'] = [];
        $data->passwords['field3'] = "Some text";

        $verifier = new DataVerify($data);
        $verifier
            ->field("passwords")->required->array
                ->subfield("field1", "field2")->required->string
                ->subfield("field3")->required->string;

        $this->assertFalse($verifier->verify());

        $errors = $verifier->getErrors();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString("is required", $errors[0]['message']);
    }

    public function testArraySubfieldsInvalid_BadType()
    {
        try {
            $data = new stdClass();
            $data->passwords = [];
            $data->passwords['field1'] = [];
            $data->passwords['field1']['field2'] = 12345;
            $data->passwords['field3'] = "Text ok";

            $verifier = new DataVerify($data);
            $verifier
                ->field("passwords")->required->array
                ->subfield("field1", "field2")->required->string
                ->subfield("field3")->required->string;

            $this->assertFalse($verifier->verify());

            $errors = $verifier->getErrors();
            $this->assertNotEmpty($errors);
            $this->assertStringContainsString("must be a string", $errors[0]['message']);
        } catch (\Exception $e) {
            $this->assertStringContainsString("string", $e->getMessage());
        }
    }

    public function testArraySubfieldsInvalid_EmptyValue()
    {
        $data = new stdClass();
        $data->passwords = [];
        $data->passwords['field1'] = [];
        $data->passwords['field1']['field2'] = "";
        $data->passwords['field3'] = "Some text";

        $verifier = new DataVerify($data);
        $verifier
            ->field("passwords")->required->array
            ->subfield("field1", "field2")->required->string
            ->subfield("field3")->required->string;

        $this->assertFalse($verifier->verify());

        $errors = $verifier->getErrors();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString("is required", $errors[0]['message']);
    }

    public function testObjectSubfieldsValid()
    {
        $data = new stdClass();
        $data->passwords = new stdClass();
        $data->passwords->field1 = new stdClass();
        $data->passwords->field1->field2 = "Text test";
        $data->passwords->field3 = "Not empty";

        $verifier = new DataVerify($data);
        $verifier
            ->field("passwords")->required->object
            ->subfield("field1", "field2")->required->string
            ->subfield("field3")->required->string;

        $this->assertTrue($verifier->verify());
    }

    public function testObjectSubfieldsInvalid_BadType()
    {
        $data = new stdClass();
        $data->passwords = new stdClass();
        $data->passwords->field1 = new stdClass();
        $data->passwords->field1->field2 = ['notEmpty'];
        $data->passwords->field3 = "Valid text";

        $verifier = new DataVerify($data);
        $verifier
            ->field("passwords")->required->object
            ->subfield("field1", "field2")->required->string
            ->subfield("field3")->required->string;

        $this->assertFalse($verifier->verify());

        $errors = $verifier->getErrors();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString("must be a string", $errors[0]['message']);
    }

    public function testObjectSubfieldsInvalid_MissingSubfield()
    {
        $data = new stdClass();
        $data->passwords = new stdClass();
        $data->passwords->field1 = new stdClass();

        $verifier = new DataVerify($data);
        $verifier
            ->field("passwords")->required->object
            ->subfield("field1", "field2")->required->string;

        $this->assertFalse($verifier->verify());

        $errors = $verifier->getErrors();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString("is required", $errors[0]['message']);
    }

    public function testMultipleFieldsAndSubfields()
    {
        $data = new stdClass();
        $data->company = new stdClass();
        $data->company->name = "Acme Inc";
        $data->passwords = [];
        $data->passwords['credentials'] = [];
        $data->passwords['credentials']['nested'] = [];
        $data->passwords['credentials']['nested']['deepField'] = "pwd";

        $verifier = new DataVerify($data);
        $verifier
            ->field('company')->required->object
                ->subfield('name')->required->string
            ->field("passwords")->required->array
                ->subfield("credentials", "nested", "deepField")->required->string;

        $this->assertTrue($verifier->verify());
    }

    public function testPasswordsConstraintNotLeakedToCompanySubfield()
    {
        $data = new stdClass();
        $data->company = new stdClass();
        $data->company->name = "Acme Inc";
        $data->company->address = "123 Main St";
        $data->company->employees = ["Alice", "Bob"];

        $data->passwords = "not_an_array";

        $verifier = new DataVerify($data);
        $verifier
            ->field('company')->required->object
                ->subfield('name')->required->string
                ->subfield('employees')->required->array
            ->field("passwords")->required->array;

        $this->assertFalse($verifier->verify());

        $errors = $verifier->getErrors();

        $this->assertSame("passwords", $errors[0]['field']);
        $this->assertSame("array", $errors[0]['test']);
    }

    public function testArrayIndexSubfieldsValid()
    {
        $data = new stdClass();
        $data->orders = [
            (object)[
                'id' => 1,
                'items' => [
                    (object)['name' => 'Product A'],
                    (object)['name' => 'Product B'],
                    (object)['name' => 'Product C']
                ]
            ]
        ];

        $verifier = new DataVerify($data);
        $verifier
            ->field('orders')->required->array
                ->subfield('0', 'items', '2', 'name')->required->string->minLength(3);

        $this->assertTrue($verifier->verify());
    }

    public function testArrayIndexSubfieldsInvalid()
    {
        $data = new stdClass();
        $data->orders = [
            (object)[
                'id' => 1,
                'items' => [
                    (object)['name' => 'Product A'],
                    (object)['name' => 'Product B'],
                    (object)['name' => 'AB'] // Too short
                ]
            ]
        ];

        $verifier = new DataVerify($data);
        $verifier
            ->field('orders')->required->array
                ->subfield('0', 'items', '2', 'name')->required->string->minLength(3);

        $this->assertFalse($verifier->verify());

        $errors = $verifier->getErrors();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('at least 3', $errors[0]['message']);
    }

    public function testDeeplyNestedArrayIndexSubfields()
    {
        $data = new stdClass();
        $data->warehouses = [
            (object)[
                'name' => 'Paris',
                'inventory' => [
                    (object)[
                        'product' => (object)[
                            'sku' => 'ABC-123',
                            'variants' => [
                                (object)['size' => 'S', 'stock' => 10],
                                (object)['size' => 'M', 'stock' => 50],
                                (object)['size' => 'L', 'stock' => 25]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $verifier = new DataVerify($data);
        $verifier
            ->field('warehouses')->required->array
                ->subfield('0', 'inventory', '0', 'product', 'variants', '1', 'stock')
                    ->required->int->between(0, 1000);

        $this->assertTrue($verifier->verify());
    }

    public function testMixedArrayAndObjectNesting()
    {
        $data = new stdClass();
        $data->users = [
            (object)[
                'profile' => (object)[
                    'addresses' => [
                        (object)['city' => 'Paris', 'country' => 'FR'],
                        (object)['city' => 'Lyon', 'country' => 'FR']
                    ]
                ]
            ]
        ];

        $verifier = new DataVerify($data);
        $verifier
            ->field('users')->required->array
                ->subfield('0', 'profile', 'addresses', '1', 'city')->required->string
                ->subfield('0', 'profile', 'addresses', '1', 'country')->required->regex('/^[A-Z]{2}$/');

        $this->assertTrue($verifier->verify());
    }
}