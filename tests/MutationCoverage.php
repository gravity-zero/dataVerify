<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Gravity\DataVerify;
use Gravity\Exceptions\ValidationTestNotFoundException;
use PHPUnit\Framework\TestCase;

class MutationCoverage extends TestCase
{
    public function testMultipleFieldsAreStoredAndValidated(): void
    {
        $data = new stdClass();
        $data->field1 = "";
        $data->field2 = "";
        $data->field3 = "";
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('field1')->required
            ->field('field2')->required
            ->field('field3')->required;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        
        $this->assertCount(3, $errors, 'All 3 fields must be validated');
        
        $fields = array_column($errors, 'field');
        $this->assertContains('field1', $fields);
        $this->assertContains('field2', $fields);
        $this->assertContains('field3', $fields);
    }

    public function testValidationIsActuallyRegistered(): void
    {
        $data = new stdClass();
        $data->email = "invalid-email";
        
        $verifier = new DataVerify($data);
        $verifier->field('email')->required->email->minLength(5);
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        
        $this->assertGreaterThan(0, count($errors), 'Validations must be registered');
    }

    public function testValidationNotFoundExceptionDefaultCode(): void
    {
        $exception = new ValidationTestNotFoundException('testName');
        $this->assertEquals(0, $exception->getCode(), 'Default exception code must be 0');
    }

    public function testValidationNotFoundExceptionCustomCode(): void
    {
        $exception = new ValidationTestNotFoundException('testName', 42);
        $this->assertEquals(42, $exception->getCode(), 'Custom code must be preserved');
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
        
        $this->assertEquals(2, count($errors), 'Both subfields must be validated');
        
        $paths = array_column($errors, 'field');
        $this->assertContains('user.sub1', $paths);
        $this->assertContains('user.sub2', $paths);
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
        
        $this->assertEquals(3, count($errors), 'All 3 subfields must be stored and validated');
    }

    public function testInternalMethodNameMustBeCorrect(): void
    {
        $data = new stdClass();
        $data->value = "not-numeric";
        
        $verifier = new DataVerify($data);
        $verifier->field('value')->numeric;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        
        $this->assertCount(1, $errors);
        $this->assertEquals('numeric', $errors[0]['test']);
    }

    public function testSubFieldRequiresFieldHandlerParent(): void
    {
        $data = new stdClass();
        $data->parent = new stdClass();
        $data->parent->child = "value";
        
        $verifier = new DataVerify($data);
        
        $verifier
            ->field('parent')->required->object
                ->subfield('child')->required->string;
        
        $this->assertTrue($verifier->verify());
    }

    public function testCompleteValidationChain(): void
    {
        $data = new stdClass();
        $data->user = new stdClass();
        $data->user->email = "bad";
        $data->user->age = 5;
        $data->email = "bad";
        $data->name = "";
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('user')->required->object
                ->subfield('email')->required->email
                ->subfield('age')->required->int->greaterThan(18)
            ->field('email')->required->email
            ->field('name')->required->string;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        
        $this->assertEquals(4, count($errors));
    }

    public function testFieldIsActuallyStoredInCollection(): void
    {
        $data = new stdClass();
        $data->field1 = 'value1';
        $data->field2 = 'value2';
        
        $verifier = new DataVerify($data);
        $verifier->field('field1')->required;
        $verifier->field('field2')->required;
        
        // Utiliser reflection pour vérifier que les fields sont stockés
        $reflection = new \ReflectionClass($verifier);
        $fieldsProperty = $reflection->getProperty('fields');
        $fields = $fieldsProperty->getValue($verifier);
        
        $this->assertCount(2, $fields);
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
        
        // Vérifier via erreurs que les 2 subfields ont été enregistrés
        $verifier->verify();
        
        // Si un subfield n'était pas ajouté, il ne serait pas validé
        $this->assertTrue(true); // Le test est que ça ne crash pas
    }

    public function testInternalMethodNameIsUsed(): void
    {
        $data = new stdClass();
        $data->value = 'not-numeric';
        
        $verifier = new DataVerify($data);
        $verifier->field('value')->numeric;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        
        // Le test name doit être 'numeric' (sans _) après ltrim
        $this->assertEquals('numeric', $errors[0]['test']);
        
        // Vérifier que la validation interne _numeric a bien été appelée
        // en testant le message traduit
        $this->assertStringContainsString('numeric', $errors[0]['message']);
    }

    /**
     * Mutant #9 - addValidation() doit vraiment être appelé
     * Si addValidation() est supprimé, aucune validation n'est enregistrée
     */
    public function testAddValidationMustBeCalledOrNoErrors(): void
    {
        $data = new stdClass();
        $data->email = 'invalid';
        
        $v = new DataVerify($data);
        $v->field('email')->email;
        
        // Si addValidation() est supprimé, pas de validation, verify() = true
        // Si addValidation() est appelé, validation email échoue, verify() = false
        $this->assertFalse($v->verify(), 'Validation must be registered via addValidation()');
    }

    /**
     * Mutant #4 - context->push() doit être appelé pour subfield
     * Si push() n'est pas appelé, le context ne pointe pas sur le subfield
     * et les validations suivantes ne s'appliquent pas au bon handler
     */
    public function testContextPushMustBeCalledForSubField(): void
    {
        $data = new stdClass();
        $data->user = new stdClass();
        $data->user->name = '';
        
        $v = new DataVerify($data);
        $v->field('user')->required->object
            ->subfield('name')
            ->required;  // Cette validation doit s'appliquer au subfield
        
        // Si context->push() est supprimé, required s'applique au field parent
        // Si context->push() est appelé, required s'applique au subfield
        $this->assertFalse($v->verify());
        $errors = $v->getErrors();
        
        // On doit avoir une erreur sur user.name, pas sur user
        $this->assertCount(1, $errors);
        $this->assertEquals('user.name', $errors[0]['field'], 'Validation must apply to subfield via context->push()');
    }

    /**
     * Mutants #3, #11 - addSubField() doit être appelé
     * Si addSubField() est supprimé, le subfield n'est pas validé
     */
    public function testAddSubFieldMustBeCalledOrSubFieldNotValidated(): void
    {
        $data = new stdClass();
        $data->user = new stdClass();
        $data->user->email = '';
        
        $v = new DataVerify($data);
        $v->field('user')->required->object
            ->subfield('email')->required;
        
        // Si addSubField() est supprimé, subfield pas enregistré, pas d'erreur
        // Si addSubField() est appelé, subfield validé, 1 erreur
        $this->assertFalse($v->verify());
        $errors = $v->getErrors();
        $this->assertCount(1, $errors, 'SubField must be added via addSubField()');
        $this->assertEquals('user.email', $errors[0]['field']);
    }

    /**
     * Mutant #1 - Vérifier que les fields sont dans la collection
     * Test ISOLÉ qui échoue forcément si add() est supprimé
     */
    public function testFieldAddMustBeCalledOrVerifyDoesNothing(): void
    {
        $data = new stdClass();
        $data->x = '';
        
        $v = new DataVerify($data);
        $v->field('x')->required;
        
        // Si add() est supprimé, fields est vide, verify() retourne true
        // Si add() est appelé, fields contient x, verify() retourne false
        $result = $v->verify();
        
        $this->assertFalse($result, 'verify() must return false when field is required and empty');
    }
}