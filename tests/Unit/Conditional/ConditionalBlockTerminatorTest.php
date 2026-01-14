<?php

use Gravity\DataVerify;
use PHPUnit\Framework\TestCase;

/**
 * Tests for conditional block terminators
 * 
 * Validates that conditional blocks are properly terminated by:
 * - new when()
 * - new field()
 * - new subfield()
 */
class ConditionalBlockTerminatorTest extends TestCase
{
    public function testWhenTerminatesPreviousConditionalBlock(): void
    {
        $data = new \stdClass();
        $data->x = 1;
        $data->y = 1;
        $data->field = 'value';
        
        $v = new DataVerify($data);
        $v->field('field')
                ->when('x', '=', 1)
                    ->then->minLength(10)       // Block 1
                ->when('y', '=', 1)              // Terminates Block 1
                    ->then->maxLength(3);        // Block 2
        
        $this->assertFalse($v->verify());
        $errors = $v->getErrors();
        
        // Should have both errors from both blocks
        $this->assertCount(2, $errors);
        
        $minLengthError = array_filter($errors, fn($e) => $e['test'] === 'minLength');
        $maxLengthError = array_filter($errors, fn($e) => $e['test'] === 'maxLength');
        
        $this->assertNotEmpty($minLengthError, 'Block 1 should be evaluated');
        $this->assertNotEmpty($maxLengthError, 'Block 2 should be evaluated');
    }
    
    public function testWhenAfterThenDoesNotThrowError(): void
    {
        $data = new \stdClass();
        $data->x = 1;
        $data->y = 2;
        $data->field = null;
        
        $v = new DataVerify($data);
        
        // This should not throw - when() should auto-finalize previous block
        $v->field('field')
                ->when('x', '=', 1)
                    ->then->required
                ->when('y', '=', 2)
                    ->then->string;
        
        $this->assertFalse($v->verify());
    }
    
    public function testFieldTerminatesConditionalBlock(): void
    {
        $data = new \stdClass();
        $data->x = 1;
        $data->field1 = null;
        $data->field2 = null;
        
        $v = new DataVerify($data);
        $v->field('field1')
                ->when('x', '=', 1)
                    ->then->string          // Conditional on field1
            ->field('field2')               // Terminates conditional block
                ->required;                  // Unconditional on field2
        
        $this->assertFalse($v->verify());
        $errors = $v->getErrors();
        
        // field2 required should fail
        $field2Errors = array_filter($errors, fn($e) => $e['field'] === 'field2');
        $this->assertNotEmpty($field2Errors);
    }
    
    public function testFieldAfterThenDoesNotThrowError(): void
    {
        $data = new \stdClass();
        $data->x = 1;
        $data->field1 = 'value';
        $data->field2 = 'value';
        
        $v = new DataVerify($data);
        
        // This should not throw - field() should auto-finalize conditional block
        $v->field('field1')
                ->when('x', '=', 1)
                    ->then->required
            ->field('field2')
                ->required;
        
        $this->assertTrue($v->verify());
    }
    
    public function testSubfieldTerminatesConditionalBlock(): void
    {
        $data = new \stdClass();
        $data->parent = new \stdClass();
        $data->parent->x = 1;
        $data->parent->sub1 = 'value';
        $data->parent->sub2 = null;
        
        $v = new DataVerify($data);
        $v->field('parent')->object
            ->subfield('sub1')
                ->when('parent.x', '=', 1)
                    ->then->minLength(10)       // Conditional on sub1
            ->subfield('sub2')                  // Terminates conditional block
                ->required;                      // Unconditional on sub2
        
        $this->assertFalse($v->verify());
        $errors = $v->getErrors();
        
        // Should have errors from both
        $this->assertGreaterThanOrEqual(2, count($errors));
    }
    
    public function testSubfieldAfterThenDoesNotThrowError(): void
    {
        $data = new \stdClass();
        $data->parent = new \stdClass();
        $data->parent->x = 1;
        $data->parent->sub1 = 'value';
        $data->parent->sub2 = 'value';
        
        $v = new DataVerify($data);
        
        // This should not throw - subfield() should auto-finalize conditional block
        $v->field('parent')->object
            ->subfield('sub1')
                ->when('parent.x', '=', 1)
                    ->then->required
            ->subfield('sub2')
                ->required;
        
        $this->assertTrue($v->verify());
    }
    
    public function testImplicitTerminationAtEndOfChain(): void
    {
        $data = new \stdClass();
        $data->x = 1;
        $data->field = 'ab';
        
        $v = new DataVerify($data);
        $v->field('field')
                ->when('x', '=', 1)
                    ->then->minLength(5)
                        ->maxLength(10);
        // Implicit termination at end of chain
        
        $this->assertFalse($v->verify());
        $errors = $v->getErrors();
        
        $minLengthError = array_filter($errors, fn($e) => $e['test'] === 'minLength');
        $this->assertNotEmpty($minLengthError);
    }
    
    public function testNoTerminatorNeededForSingleConditionalBlock(): void
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
        
        // All three conditional validations should be in same block
        $this->assertGreaterThanOrEqual(1, count($errors));
    }
    
    public function testMultipleFieldsWithConditionalBlocks(): void
    {
        $data = new \stdClass();
        $data->x = 1;
        $data->y = 1;
        $data->field1 = null;
        $data->field2 = 'ab';
        $data->field3 = 'value';
        
        $v = new DataVerify($data);
        $v->field('field1')
                ->when('x', '=', 1)
                    ->then->required
            ->field('field2')
                ->when('y', '=', 1)
                    ->then->minLength(5)
            ->field('field3')
                ->required;
        
        $this->assertFalse($v->verify());
        $errors = $v->getErrors();
        
        // field1: required fails, field2: minLength fails, field3: passes
        $this->assertCount(2, $errors);
    }
    
    public function testConditionalBlockStateDoesNotLeakBetweenFields(): void
    {
        $data = new \stdClass();
        $data->x = 1;
        $data->field1 = 'value';
        $data->field2 = null;
        
        $v = new DataVerify($data);
        $v->field('field1')
                ->when('x', '=', 1)
                    ->then->required
            ->field('field2')               // Should NOT inherit conditional from field1
                ->required;
        
        $this->assertFalse($v->verify());
        $errors = $v->getErrors();
        
        // Only field2 should fail (unconditional required)
        $this->assertCount(1, $errors);
        $this->assertEquals('field2', $errors[0]['field']);
    }
    
    public function testConditionalBlockStateDoesNotLeakBetweenSubfields(): void
    {
        $data = new \stdClass();
        $data->parent = new \stdClass();
        $data->parent->x = 1;
        $data->parent->sub1 = 'value';
        $data->parent->sub2 = null;
        
        $v = new DataVerify($data);
        $v->field('parent')->object
            ->subfield('sub1')
                ->when('parent.x', '=', 1)
                    ->then->minLength(10)
            ->subfield('sub2')              // Should NOT inherit conditional from sub1
                ->required;
        
        $this->assertFalse($v->verify());
        $errors = $v->getErrors();
        
        // Both should fail: sub1 minLength and sub2 required
        $this->assertGreaterThanOrEqual(2, count($errors));
        
        $sub2Errors = array_filter($errors, fn($e) => str_contains($e['field'], 'sub2'));
        $this->assertNotEmpty($sub2Errors);
    }
    
    public function testAlternatingConditionalAndUnconditionalRules(): void
    {
        $data = new \stdClass();
        $data->x = 1;
        $data->y = 0;  // This condition will be false
        $data->field = 'ab';
        
        $v = new DataVerify($data);
        $v->field('field')
                ->minLength(3)                  // Unconditional
                ->when('x', '=', 1)
                    ->then->maxLength(5)         // Conditional (triggered)
                ->when('y', '=', 1)
                    ->then->containsUpper        // Conditional (NOT triggered)
                ->minLength(4);                  // This is INSIDE the last conditional block!
        
        $this->assertFalse($v->verify());
        $errors = $v->getErrors();
        
        // Should fail on minLength(4) because it's in the y=1 block which is false
        // So only unconditional minLength(3) fails
        $this->assertCount(1, $errors);
        $this->assertEquals('minLength', $errors[0]['test']);
    }
}
