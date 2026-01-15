<?php

use Gravity\DataVerify;
use PHPUnit\Framework\TestCase;

/**
 * Tests for deferred conditional evaluation
 * 
 * Tests the new behavior where conditions are stored during construction
 * and evaluated during verify() instead of being evaluated immediately.
 */
class DeferredConditionalEvaluationTest extends TestCase
{
    public function testDefaultRulesPlusConditionalRequired(): void
    {
        $data = new \stdClass();
        $data->user = new \stdClass();
        $data->user->id = 123;
        $data->user->password = 'short';
        
        $v = new DataVerify($data);
        $v->field('user')->object
            ->subfield('password')
                ->minLength(12)                      // Default rule - always
                ->containsUpper                      // Default rule - always
                ->when('user.id', '!=', null)
                    ->then->required;                // Conditional - if user.id exists
        
        $this->assertFalse($v->verify());
        $errors = $v->getErrors();
        
        // Should fail on minLength (default rule), not required
        $this->assertGreaterThan(0, count($errors));
        $minLengthError = array_filter($errors, fn($e) => $e['test'] === 'minLength');
        $this->assertNotEmpty($minLengthError, 'minLength validation should fail');
    }
    
    public function testConditionalNotTriggeredWithDefaultRules(): void
    {
        $data = new \stdClass();
        $data->user = new \stdClass();
        $data->user->id = null;
        $data->user->password = '';
        
        $v = new DataVerify($data);
        $v->field('user')->object
            ->subfield('password')
                ->string                             // Default rule - always
                ->when('user.id', '!=', null)
                    ->then->required;                // Conditional - NOT triggered
        
        // Password is empty but required is conditional and not triggered
        // string validation skips empty values unless required
        $this->assertTrue($v->verify(), 'Validation should pass when conditional not triggered');
    }
    
    public function testMultipleConditionalBlocksOnSameField(): void
    {
        $data = new \stdClass();
        $data->user = new \stdClass();
        $data->user->id = 123;
        $data->user->role = 'admin';
        $data->user->password = 'weak';
        
        $v = new DataVerify($data);
        $v->field('user')->object
            ->subfield('password')
                ->minLength(5)                              // Default: always
                ->when('user.id', '!=', null)
                    ->then->required                        // Block 1: if user.id exists
                ->when('user.role', '=', 'admin')           // Block 2 starts here
                    ->then->minLength(12)                   // Block 2: if role=admin
                        ->containsSpecialCharacter;         // Block 2: if role=admin
        
        $this->assertFalse($v->verify());
        $errors = $v->getErrors();
        
        // Should fail on Block 2 rules (minLength 12 and containsSpecialCharacter)
        $this->assertGreaterThan(0, count($errors));
    }
    
    public function testConditionalBlockTerminatedByNewField(): void
    {
        $data = new \stdClass();
        $data->x = 1;
        $data->field1 = null;
        $data->field2 = 'value';
        
        $v = new DataVerify($data);
        $v->field('field1')
                ->when('x', '=', 1)
                    ->then->required            // Conditional on field1
            ->field('field2')                   // Terminates previous conditional block
                ->required;                      // Unconditional on field2
        
        $this->assertFalse($v->verify());
        $errors = $v->getErrors();
        
        // Should have error on field1 (conditional required triggered)
        $field1Errors = array_filter($errors, fn($e) => $e['field'] === 'field1');
        $this->assertNotEmpty($field1Errors);
    }
    
    public function testConditionalBlockTerminatedByNewSubfield(): void
    {
        $data = new \stdClass();
        $data->parent = new \stdClass();
        $data->parent->x = 1;
        $data->parent->sub1 = null;
        $data->parent->sub2 = null;
        
        $v = new DataVerify($data);
        $v->field('parent')->object
            ->subfield('sub1')
                ->when('parent.x', '=', 1)
                    ->then->required                // Conditional on sub1
            ->subfield('sub2')                      // Terminates previous conditional block
                ->required;                          // Unconditional on sub2
        
        $this->assertFalse($v->verify());
        $errors = $v->getErrors();
        
        // Should have errors on both sub1 and sub2
        $this->assertCount(2, $errors);
    }
    
    public function testConditionalBlockTerminatedByNewWhen(): void
    {
        $data = new \stdClass();
        $data->x = 1;
        $data->y = 2;
        $data->field = null;
        
        $v = new DataVerify($data);
        $v->field('field')
                ->when('x', '=', 1)
                    ->then->required                // Block 1
                ->when('y', '=', 2)                 // Terminates Block 1, starts Block 2
                    ->then->string;                  // Block 2
        
        $this->assertFalse($v->verify());
        $errors = $v->getErrors();
        
        // Should fail on required (Block 1)
        $requiredError = array_filter($errors, fn($e) => $e['test'] === 'required');
        $this->assertNotEmpty($requiredError);
    }
    
    public function testMultipleValidationsInSameConditionalBlock(): void
    {
        $data = new \stdClass();
        $data->role = 'admin';
        $data->password = 'weak';
        
        $v = new DataVerify($data);
        $v->field('password')
                ->when('role', '=', 'admin')
                    ->then->required
                        ->minLength(12)
                        ->containsUpper
                        ->containsLower
                        ->containsSpecialCharacter;
        
        $this->assertFalse($v->verify());
        $errors = $v->getErrors();
        
        // All conditional validations should be evaluated
        $this->assertGreaterThanOrEqual(3, count($errors));
    }
    
    public function testConditionalBlockNotAppliedWhenConditionFalse(): void
    {
        $data = new \stdClass();
        $data->role = 'user';
        $data->password = 'weak';
        
        $v = new DataVerify($data);
        $v->field('password')
                ->minLength(4)                      // Default: always passes
                ->when('role', '=', 'admin')
                    ->then->minLength(12)           // Conditional: not applied
                        ->containsSpecialCharacter; // Conditional: not applied
        
        $this->assertTrue($v->verify(), 'Should pass when conditional block not triggered');
    }
    
    public function testAndConditionsInConditionalBlock(): void
    {
        $data = new \stdClass();
        $data->tier = 'premium';
        $data->status = 'active';
        $data->features = null;
        
        $v = new DataVerify($data);
        $v->field('features')
                ->array                                     // Default
                ->when('tier', '=', 'premium')
                ->and('status', '=', 'active')
                    ->then->required;                       // Conditional: both must be true
        
        $this->assertFalse($v->verify());
        $errors = $v->getErrors();
        
        $requiredError = array_filter($errors, fn($e) => $e['test'] === 'required');
        $this->assertNotEmpty($requiredError);
    }
    
    public function testAndConditionsNotAllMet(): void
    {
        $data = new \stdClass();
        $data->tier = 'premium';
        $data->status = 'inactive';
        $data->features = null;
        
        $v = new DataVerify($data);
        $v->field('features')
                ->array
                ->when('tier', '=', 'premium')
                ->and('status', '=', 'active')
                    ->then->required;
        
        // Status is not active, so AND condition fails
        $this->assertTrue($v->verify(), 'Should pass when AND condition not fully met');
    }
    
    public function testOrConditionsInConditionalBlock(): void
    {
        $data = new \stdClass();
        $data->payment_method = 'crypto';
        $data->kyc_document = null;
        
        $v = new DataVerify($data);
        $v->field('kyc_document')
                ->string
                ->when('payment_method', '=', 'crypto')
                ->or('payment_method', '=', 'wire')
                    ->then->required;
        
        $this->assertFalse($v->verify());
        $errors = $v->getErrors();
        
        $requiredError = array_filter($errors, fn($e) => $e['test'] === 'required');
        $this->assertNotEmpty($requiredError);
    }
    
    public function testOrConditionsOneMatches(): void
    {
        $data = new \stdClass();
        $data->payment_method = 'card';
        $data->kyc_document = null;
        
        $v = new DataVerify($data);
        $v->field('kyc_document')
                ->string
                ->when('payment_method', '=', 'crypto')
                ->or('payment_method', '=', 'wire')
                    ->then->required;
        
        // Neither crypto nor wire, so OR condition fails
        $this->assertTrue($v->verify(), 'Should pass when OR condition not met');
    }
    
    public function testComplexScenarioMixingDefaultAndConditional(): void
    {
        $data = new \stdClass();
        $data->user = new \stdClass();
        $data->user->id = 456;
        $data->user->role = 'user';
        $data->user->email = 'valid@example.com';
        $data->user->password = 'ValidPass123!';
        
        $v = new DataVerify($data);
        $v->field('user')->required->object
            ->subfield('email')
                ->email                                     // Always
                ->when('user.id', '!=', null)
                    ->then->required                        // If has ID
            ->subfield('password')
                ->minLength(8)                              // Always
                ->containsUpper                             // Always
                ->when('user.id', '!=', null)
                    ->then->required                        // If has ID
                ->when('user.role', '=', 'admin')
                    ->then->minLength(16)                   // If admin
                        ->containsSpecialCharacter;         // If admin
        
        // User is not admin, so admin rules should not apply
        $this->assertTrue($v->verify(), 'Should pass for non-admin with valid password');
    }
    
    public function testDefaultRulesAppliedEvenWhenConditionalBlockExists(): void
    {
        $data = new \stdClass();
        $data->x = 0;  // Condition will be false
        $data->field = 'ab';
        
        $v = new DataVerify($data);
        $v->field('field')
                ->minLength(5)                      // Default: should always apply
                ->when('x', '=', 1)
                    ->then->maxLength(10);          // Conditional: will not apply
        
        $this->assertFalse($v->verify(), 'Default rules should apply regardless of conditional');
        $errors = $v->getErrors();
        
        $minLengthError = array_filter($errors, fn($e) => $e['test'] === 'minLength');
        $this->assertNotEmpty($minLengthError, 'Default minLength should fail');
    }
    
    public function testConditionalBlockWithNoDefaultRules(): void
    {
        $data = new \stdClass();
        $data->x = 1;
        $data->field = null;
        
        $v = new DataVerify($data);
        $v->field('field')
                ->when('x', '=', 1)
                    ->then->required
                        ->string
                        ->minLength(5);
        
        $this->assertFalse($v->verify());
        $errors = $v->getErrors();
        
        // All validations are conditional and should be evaluated
        $this->assertGreaterThan(0, count($errors));
    }
    
    public function testNestedConditionalEvaluation(): void
    {
        $data = new \stdClass();
        $data->parent = new \stdClass();
        $data->parent->enabled = true;
        $data->parent->child = new \stdClass();
        $data->parent->child->value = 'x';
        
        $v = new DataVerify($data);
        $v->field('parent')->object
            ->subfield('child')->object
                ->subfield('child', 'value')
                    ->when('parent.enabled', '=', true)
                        ->then->minLength(5);
        
        $this->assertFalse($v->verify());
        $errors = $v->getErrors();
        
        $this->assertCount(1, $errors);
        $this->assertEquals('parent.child.value', $errors[0]['field']);
    }
}
