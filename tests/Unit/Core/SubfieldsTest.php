<?php

use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;

class SubfieldsTest extends TestCase
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
        $this->assertCount(1, $errors);
        $this->assertStringContainsString("is required", $errors[0]['message']);
    }

    public function testObjectSubfieldsValid()
    {
        $data = new stdClass();
        $data->user = new stdClass();
        $data->user->profile = new stdClass();
        $data->user->profile->name = "John";

        $verifier = new DataVerify($data);
        $verifier
            ->field("user")->required->object
            ->subfield("profile", "name")->required->string;

        $this->assertTrue($verifier->verify());
    }

    public function testObjectSubfieldsInvalid_BadType()
    {
        $data = new stdClass();
        $data->user = new stdClass();
        $data->user->age = "not a number";

        $verifier = new DataVerify($data);
        $verifier
            ->field("user")->required->object
            ->subfield("age")->required->int;

        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        $this->assertNotEmpty($errors);
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
        $data->user = new stdClass();
        $data->user->email = "test@test.com";
        $data->company = new stdClass();
        $data->company->name = "ACME";

        $verifier = new DataVerify($data);
        $verifier
            ->field("user")->required->object
                ->subfield("email")->required->email
            ->field("company")->required->object
                ->subfield("name")->required->string;

        $this->assertTrue($verifier->verify());
    }

    public function testPasswordsConstraintNotLeakedToCompanySubfield()
    {
        $data = new stdClass();
        $data->passwords = new stdClass();
        $data->passwords->main = "short";
        $data->company = new stdClass();
        $data->company->name = "ok";

        $verifier = new DataVerify($data);
        $verifier
            ->field("passwords")->required->object
                ->subfield("main")->required->minLength(10)
            ->field("company")->required->object
                ->subfield("name")->required->string;

        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();

        $this->assertCount(1, $errors);
        $this->assertEquals("passwords.main", $errors[0]['field']);
    }

    public function testArrayIndexSubfieldsValid()
    {
        $data = new stdClass();
        $data->items = ["first", "second", "third"];

        $verifier = new DataVerify($data);
        $verifier
            ->field("items")->required->array
            ->subfield("0")->required->string
            ->subfield("1")->required->string
            ->subfield("2")->required->string;

        $this->assertTrue($verifier->verify());
    }

    public function testArrayIndexSubfieldsInvalid()
    {
        $data = new stdClass();
        $data->items = ["first", "", "third"];

        $verifier = new DataVerify($data);
        $verifier
            ->field("items")->required->array
            ->subfield("0")->required->string
            ->subfield("1")->required->string
            ->subfield("2")->required->string;

        $this->assertFalse($verifier->verify());

        $errors = $verifier->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals("items.1", $errors[0]['field']);
        $this->assertStringContainsString("is required", $errors[0]['message']);
    }

    public function testDeeplyNestedArrayIndexSubfields()
    {
        $data = new stdClass();
        $data->matrix = [
            ["a", "b", "c"],
            ["d", "e", "f"],
            ["g", "h", "i"]
        ];

        $verifier = new DataVerify($data);
        $verifier
            ->field("matrix")->required->array
            ->subfield("0", "0")->required->string
            ->subfield("1", "1")->required->string
            ->subfield("2", "2")->required->string;

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

    public function testMultipleSubFieldsAreStored(): void
    {
        $data = new stdClass();
        $data->parent = new stdClass();
        $data->parent->child1 = "";
        $data->parent->child2 = "";
        $data->parent->child3 = "";
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('parent')->required->object
                ->subfield('child1')->required->string
                ->subfield('child2')->required->string
                ->subfield('child3')->required->string;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        
        $this->assertEquals(3, count($errors));
    }

    public function testSubFieldsRequireBothAddAndPush(): void
    {
        $data = new stdClass();
        $data->user = new stdClass();
        $data->user->sub1 = "";
        $data->user->sub2 = "";
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('user')->required->object
                ->subfield('sub1')->required->string
                ->subfield('sub2')->required->string;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        
        $this->assertEquals(2, count($errors));
        
        $paths = array_column($errors, 'field');
        $this->assertContains('user.sub1', $paths);
        $this->assertContains('user.sub2', $paths);
    }

    public function testSubFieldIsActuallyStoredInParent(): void
    {
        $data = new stdClass();
        $data->parent = new stdClass();
        $data->parent->child1 = 'value1';
        $data->parent->child2 = 'value2';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('parent')->required->object
                ->subfield('child1')->required
                ->subfield('child2')->required;
        
        $this->assertTrue($verifier->verify());
    }

    public function testContextPushMustBeCalledForSubField(): void
    {
        $data = new stdClass();
        $data->user = new stdClass();
        $data->user->name = '';
        
        $v = new DataVerify($data);
        $v->field('user')->required->object
            ->subfield('name')
            ->required;
        
        $this->assertFalse($v->verify());
        $errors = $v->getErrors();
        
        $this->assertCount(1, $errors);
        $this->assertEquals('user.name', $errors[0]['field']);
    }

    public function testAddSubFieldMustBeCalledOrSubFieldNotValidated(): void
    {
        $data = new stdClass();
        $data->user = new stdClass();
        $data->user->email = '';
        
        $v = new DataVerify($data);
        $v->field('user')->required->object
            ->subfield('email')->required;
        
        $this->assertFalse($v->verify());
        $errors = $v->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals('user.email', $errors[0]['field']);
    }
}
