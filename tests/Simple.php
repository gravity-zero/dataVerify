<?php
use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;


class Simple extends TestCase
{
    public function __construct($name)
    {
        parent::__construct($name);
    }

    public function testInit(){
        $data = new stdClass();

        $data->email = "toto@gmail.com";
        $data->name = "toto";
        $data->random_field = "dddddddddd";

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("email")->required->disposable_email->error_message("Le champs est requis et n'accepte pas les adresses emails jetables")
            ->field("random_field")->required->string->alias("specific_field")
            ->field("name")->required->string->greater_than(3);

        $this->assertTrue($data_verifier->verify());
    }

    public function testCustomErrorMessage()
    {
        $data = new stdClass();

        $data_verifier = new DataVerify($data);
        $data_verifier->field("name")->required->error_message("the field name is required, please do it!");

        $this->assertFalse($data_verifier->verify());

        $errors = $data_verifier->get_errors();

        $this->assertEquals("the field name is required, please do it!", $errors[0]['message']);
    }

    public function testMultipleCustomErrorMessage()
    {
        $data = new stdClass();

        $data_verifier = new DataVerify($data);
        $data_verifier->field("name")->required->error_message("the field name is required, please do it!");
        $data_verifier->field("birthday")->required->error_message("it's a birthday fictive date ?");
        $data_verifier->field("halloween")->required->error_message("Halloween mode not effective yet");


        $this->assertFalse($data_verifier->verify());

        $errors = $data_verifier->get_errors();

        $this->assertEquals("the field name is required, please do it!", $errors[0]['message']);
        $this->assertEquals("it's a birthday fictive date ?", $errors[1]['message']);
        $this->assertEquals("Halloween mode not effective yet", $errors[2]['message']);
    }

    public function testRequired()
    {
        $data = new stdClass();
        $data->email = "toto@gmail.com";
        $data_verifier = new DataVerify($data);
        $data_verifier->field("email")->required;
        $this->assertTrue($data_verifier->verify());
    }

    public function testInvalidRequired()
    {
        $data = new stdClass();

        $data_verifier = new DataVerify($data);
        $data_verifier->field("name")->required;

        $this->assertFalse($data_verifier->verify());

        $errors = $data_verifier->get_errors();

        $this->assertEquals("The field 'name' failed the test 'required'", $errors[0]['message']);

    }

    public function testAliasDoesNotImpactPreviousFieldName()
    {
        $data = new stdClass();

        $data->email = "totogmail.com";
        $data->random_field = 222;
        $data->test = 2;
        $data->some_random_field = "dddddddddd";

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("email")->required->email
            ->field("random_field")->required->string->alias("specific_field")
            ->field("test")->required->int->greater_than(3)
            ->field("some_random_field")->required->int->alias("another_specific_field");

        $is_valid = $data_verifier->verify();

        $this->assertFalse($is_valid);

        $errors = $data_verifier->get_errors();

        $this->assertEquals("The field 'email' failed the test 'email'", $errors[0]['message']);
        $this->assertEquals("The field 'specific_field' failed the test 'string'", $errors[1]['message']);
        $this->assertEquals("The field 'test' failed the test 'greater_than'", $errors[2]['message']);
        $this->assertEquals("The field 'another_specific_field' failed the test 'int'", $errors[3]['message']);

    }

    public function testValidEmail()
    {
        $data = new stdClass();
        $data->email = "gravity.neo@gmail.com";

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("email")->required->disposable_email;

        $this->assertTrue($data_verifier->verify());
    }

    public function testInvalidEmail()
    {
        $data = new stdClass();
        $data->email = "test@yopmail.com";

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("email")->required->disposable_email;

        $this->assertFalse($data_verifier->verify());
        $errors = $data_verifier->get_errors();
        $this->assertEquals("The field 'email' failed the test 'disposable_email'", $errors[0]['message']);
    }

    public function testValidName()
    {
        $data = new stdClass();
        $data->name = "Doe";

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("name")->required->string->greater_than(3);

        $this->assertTrue($data_verifier->verify());
    }

    public function testInvalidName()
    {
        $data = new stdClass();
        $data->name = 'ti';

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("name")->required->string->min_length(3);

        $this->assertFalse($data_verifier->verify());
        $errors = $data_verifier->get_errors();
        $this->assertEquals("The field 'name' failed the test 'min_length'", $errors[0]['message']);
    }

    public function testBetweenDate()
    {
        $data = new stdClass();
        $data->date = "2000-01-01";
        $data->year_month = "2000-01";
        $data->year = "2000";

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("date")->required->date->between("1920-01-01", "2020-01-01")
            ->field("year_month")->required->date("Y-m")->between("1920-01-01", "2020-01-01")
            ->field("year")->required->date("Y")->between("1920-01-01", "2020-01-01");

        $is_valid = $data_verifier->verify();
        $this->assertTrue($is_valid);
    }

    public function testInvalidBetweenDate()
    {
        $data = new stdClass();
        $data->date = "1910-01-01";

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("date")->required->string->between("1920-01-01", "2020-01-01");

        $this->assertFalse($data_verifier->verify());
        $errors = $data_verifier->get_errors();
        $this->assertEquals("The field 'date' failed the test 'between'", $errors[0]['message']);
    }

    public function testBetweenInteger()
    {
        $data = new stdClass();

        $data->string_age = "5";
        $data->positive_age = 5;
        $data->negative_age = -5;
        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("string_age")->required->int->between(2, "8")
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
            ->field("string_age")->required->int->between("1", "45")
            ->field("positive_age")->required->int->between(2, 8)
            ->field("negative_age")->required->int->between(-2, 8);

        $this->assertFalse($data_verifier->verify());
        $errors = $data_verifier->get_errors();

        $this->assertEquals("The field 'string_age' failed the test 'between'", $errors[0]['message']);
        $this->assertEquals("The field 'positive_age' failed the test 'between'", $errors[1]['message']);
        $this->assertEquals("The field 'negative_age' failed the test 'between'", $errors[2]['message']);
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

        $errors = $data_verifier->get_errors();

        $this->assertEquals("The field 'string_amount' failed the test 'between'", $errors[0]['message']);
        $this->assertEquals("The field 'positive_amount' failed the test 'between'", $errors[1]['message']);
        $this->assertEquals("The field 'negative_amount' failed the test 'between'", $errors[2]['message']);

    }

    public function testValidNumeric()
    {
        $data = new stdClass();
        $data->age = 25;

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("age")->required->numeric->greater_than(18)->lower_than(100);

        $this->assertTrue($data_verifier->verify());
    }

    public function testInvalidNumeric()
    {
        $data = new stdClass();
        $data->age = 15;

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("age")->required->numeric->greater_than(18)->lower_than(100);

        $this->assertFalse($data_verifier->verify());
        $errors = $data_verifier->get_errors();
        $this->assertEquals("The field 'age' failed the test 'greater_than'", $errors[0]['message']);
    }

    public function testValidAlphanumeric()
    {
        $data = new stdClass();
        $data->username = "user123";

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("username")->required->alphanumeric;

        $this->assertTrue($data_verifier->verify());
    }

    public function testInvalidAlphanumeric()
    {
        $data = new stdClass();
        $data->username = "user@123";

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("username")->required->alphanumeric;

        $this->assertFalse($data_verifier->verify());
        $errors = $data_verifier->get_errors();
        $this->assertEquals("The field 'username' failed the test 'alphanumeric'", $errors[0]['message']);
    }

    public function testNotAlphanumeric()
    {
        $data = new stdClass();
        $data->symbol = "@#!$";

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("symbol")->required->not_alphanumeric;

        $this->assertTrue($data_verifier->verify());
    }

    public function testInvalidNotAlphanumeric()
    {
        $data = new stdClass();
        $data->symbol = "@#!é$";

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("symbol")->required->not_alphanumeric;

        $this->assertFalse($data_verifier->verify());
    }

    public function testIpAddress()
    {
        $data = new stdClass();
        $data->ip = "192.168.0.1";

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("ip")->required->ip_address;

        $this->assertTrue($data_verifier->verify());
    }

    public function testInvalidIpAddress()
    {
        $data = new stdClass();
        $data->ip = "999.999.999.999";

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("ip")->required->ip_address;

        $this->assertFalse($data_verifier->verify());
    }

    public function testValidMinLength()
    {
        $data = new stdClass();
        $data->password = "secretpassword";

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("password")->required->min_length(8);

        $this->assertTrue($data_verifier->verify());
    }

    public function testInvalidMinLength()
    {
        $data = new stdClass();
        $data->password = "short";

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("password")->required->min_length(8);

        $this->assertFalse($data_verifier->verify());
        $errors = $data_verifier->get_errors();
        $this->assertEquals("The field 'password' failed the test 'min_length'", $errors[0]['message']);
    }

    public function testValidMaxLength()
    {
        $data = new stdClass();
        $data->username = "validuser";

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("username")->required->max_length(10);

        $this->assertTrue($data_verifier->verify());
    }

    public function testInvalidMaxLength()
    {
        $data = new stdClass();
        $data->username = "averylongusername";

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("username")->required->max_length(10);

        $this->assertFalse($data_verifier->verify());
        $errors = $data_verifier->get_errors();
        $this->assertEquals("The field 'username' failed the test 'max_length'", $errors[0]['message']);
    }

    public function testValidGreaterThan()
    {
        $data = new stdClass();
        $data->score = 15;

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("score")->required->numeric->greater_than(10);

        $this->assertTrue($data_verifier->verify());
    }

    public function testInvalidGreaterThan()
    {
        $data = new stdClass();
        $data->score = 5;

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("score")->required->numeric->greater_than(10);

        $this->assertFalse($data_verifier->verify());
        $errors = $data_verifier->get_errors();
        $this->assertEquals("The field 'score' failed the test 'greater_than'", $errors[0]['message']);
    }

    public function testValidLowerThan()
    {
        $data = new stdClass();
        $data->score = 5;

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("score")->required->numeric->lower_than(10);

        $this->assertTrue($data_verifier->verify());
    }

    public function testInvalidLowerThan()
    {
        $data = new stdClass();
        $data->score = 15;

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("score")->required->numeric->lower_than(10);

        $this->assertFalse($data_verifier->verify());
        $errors = $data_verifier->get_errors();
        $this->assertEquals("The field 'score' failed the test 'lower_than'", $errors[0]['message']);
    }

    public function testInt()
    {
        $data = new stdClass();
        $data->age = 25;

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("age")->required->int;

        $this->assertTrue($data_verifier->verify());
    }

    public function testDate()
    {
        $data = new stdClass();
        $data->birthdate = "2000-01-01";

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("birthdate")->required->date;

        $this->assertTrue($data_verifier->verify());
    }

    public function testContainsSpecialCharacter()
    {
        $data = new stdClass();
        $data->password = "P@ssword123";

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("password")->required->contains_special_character;

        $this->assertTrue($data_verifier->verify());
    }

    public function testContainsLower()
    {
        $data = new stdClass();
        $data->password = "password123";

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("password")->required->contains_lower;

        $this->assertTrue($data_verifier->verify());
    }

    public function testContainsUpper()
    {
        $data = new stdClass();
        $data->password = "Password123";

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("password")->required->contains_upper;

        $this->assertTrue($data_verifier->verify());
    }

    public function testContainsNumber()
    {
        $data = new stdClass();
        $data->password = "Password123";

        $data_verifier = new DataVerify($data);
        $data_verifier
            ->field("password")->required->contains_number;

        $this->assertTrue($data_verifier->verify());
    }
}