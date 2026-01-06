<?php

use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;

class BatchTest extends TestCase
{
    public function testDefaultBatchModeCollectsAllErrors()
    {
        $data = new stdClass();
        $data->name = 123; // Not a string
        $data->age = "not_a_number"; // Not numeric
        $data->email = "invalid"; // Not an email

        $verifier = new DataVerify($data);
        $verifier
            ->field("name")->required->string->minLength(3)
            ->field("age")->required->numeric->greaterThan(18)
            ->field("email")->required->email;

        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        
        // Mode batch par défaut : toutes les erreurs sont collectées
        $this->assertCount(3, $errors);
        
        // Vérifier que chaque champ a bien ses erreurs
        $fields = array_column($errors, 'field');
        $this->assertContains('name', $fields);
        $this->assertContains('age', $fields);
        $this->assertContains('email', $fields);
    }

    public function testBatchTrueExplicitCollectsAllErrors()
    {
        $data = new stdClass();
        $data->name = ""; // Empty (required fails)
        $data->age = 15; // Valid int, but greater_than fails

        $verifier = new DataVerify($data);
        $verifier
            ->field("name")->required->string->minLength(3)
            ->field("age")->required->int->greaterThan(18);

        $this->assertFalse($verifier->verify(batch: true));
        
        $errors = $verifier->getErrors();
        
        // Batch true : toutes les erreurs
        $this->assertCount(2, $errors);
        $this->assertEquals('name', $errors[0]['field']);
        $this->assertEquals('age', $errors[1]['field']);
    }

    public function testBatchFalseStopsAtFirstFieldError()
    {
        $data = new stdClass();
        $data->name = 123; // Not a string (first error)
        $data->email = "invalid"; // Not an email (ne sera pas testé)
        $data->age = "text"; // Not numeric (ne sera pas testé)

        $verifier = new DataVerify($data);
        $verifier
            ->field("name")->required->string->minLength(5)
            ->field("email")->required->email
            ->field("age")->required->numeric;

        $this->assertFalse($verifier->verify(batch: false));
        
        $errors = $verifier->getErrors();
        
        // Batch false : arrêt au premier champ en erreur
        $this->assertCount(1, $errors);
        $this->assertEquals('name', $errors[0]['field']);
        $this->assertEquals('string', $errors[0]['test']);
    }

    public function testBatchFalseStopsAtFirstErrorPerField()
    {
        $data = new stdClass();
        $data->name = "ab"; // Valide string, mais minLength échoue

        $verifier = new DataVerify($data);
        $verifier
            ->field("name")->required->string->minLength(5)->maxLength(10);

        $this->assertFalse($verifier->verify(batch: false));
        
        $errors = $verifier->getErrors();
        
        // Batch false : première erreur du champ uniquement
        $this->assertCount(1, $errors);
        $this->assertEquals('name', $errors[0]['field']);
        $this->assertEquals('minLength', $errors[0]['test']);
        // max_length n'est pas testé car minLength a échoué
    }

    public function testBatchFalseContinuesIfFirstFieldIsValid()
    {
        $data = new stdClass();
        $data->name = "John Doe"; // Valid
        $data->email = "invalid"; // Invalid

        $verifier = new DataVerify($data);
        $verifier
            ->field("name")->required->string
            ->field("email")->required->email;

        $this->assertFalse($verifier->verify(batch: false));
        
        $errors = $verifier->getErrors();
        
        // Le premier champ est valide, donc on teste le second
        $this->assertCount(1, $errors);
        $this->assertEquals('email', $errors[0]['field']);
    }

    public function testBatchFalseFirstSubfield()
    {
        $data = new stdClass();
        $data->company = new stdClass();
        $data->company->name = 123; // Not a string
        $data->company->address = ""; // Empty (ne sera pas testé en batch false)

        $verifier = new DataVerify($data);
        $verifier
            ->field('company')->required->object
                ->subfield('name')->required->string
                ->subfield('address')->required->string;

        $this->assertFalse($verifier->verify(batch: false));
        
        $errors = $verifier->getErrors();
        
        // Batch false : arrêt au premier subfield en erreur
        $this->assertCount(1, $errors);
        $this->assertEquals('company.name', $errors[0]['field']);
        $this->assertEquals('string', $errors[0]['test']);
    }

    public function testBatchFalseSecondSubfield()
    {
        $data = new stdClass();
        $data->company = new stdClass();
        $data->company->name = "MyNameIs";
        $data->company->address = null; // false
        $data->company->email = 123; // not tested

        $verifier = new DataVerify($data);
        $verifier
            ->field('company')->required->object
                ->subfield('name')->required->string
                ->subfield('address')->required->string
                ->subfield('email')->required->email;

        $this->assertFalse($verifier->verify(batch: false));
        
        $errors = $verifier->getErrors();
        
        // Batch false : arrêt au premier subfield en erreur
        $this->assertCount(1, $errors);
        $this->assertEquals('company.address', $errors[0]['field']);
        $this->assertEquals('required', $errors[0]['test']);
    }

    public function testBatchModeReturnsTrueWhenNoErrors(): void
    {
        $data = new stdClass();
        $data->name = "John Doe";
        $data->age = 25;
        
        $verifier1 = new DataVerify($data);
        $verifier1
            ->field("name")->required->string
            ->field("age")->required->int;
        $this->assertTrue($verifier1->verify(batch: true));
        $this->assertEmpty($verifier1->getErrors());
        
        $verifier2 = new DataVerify($data);
        $verifier2
            ->field("name")->required->string
            ->field("age")->required->int;
        $this->assertTrue($verifier2->verify(batch: false));
        $this->assertEmpty($verifier2->getErrors());
    }

    public function testBatchModeComparison()
    {
        $data = new stdClass();
        $data->field1 = ""; // required fails
        $data->field2 = 123; // string fails
        $data->field3 = "invalid"; // email fails

        // Test batch mode
        $verifier1 = new DataVerify($data);
        $verifier1
            ->field("field1")->required->string
            ->field("field2")->required->string
            ->field("field3")->required->email;

        $verifier1->verify(batch: true);
        $batchErrors = $verifier1->getErrors();

        // Test fail-fast mode
        $verifier2 = new DataVerify($data);
        $verifier2
            ->field("field1")->required->string
            ->field("field2")->required->string
            ->field("field3")->required->email;

        $verifier2->verify(batch: false);
        $failFastErrors = $verifier2->getErrors();

        // Batch collecte tout, fail-fast s'arrête au premier
        $this->assertGreaterThan(count($failFastErrors), count($batchErrors));
        $this->assertEquals(1, count($failFastErrors));
    }
}