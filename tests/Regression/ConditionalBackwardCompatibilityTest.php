<?php

use Gravity\DataVerify;
use PHPUnit\Framework\TestCase;

/**
 * Backward compatibility tests
 * 
 * Ensures that existing conditional validation behavior still works
 * after implementing deferred evaluation.
 */
class ConditionalBackwardCompatibilityTest extends TestCase
{
    public function testExistingConditionalSyntaxStillWorks(): void
    {
        // This is the OLD syntax that should still work
        $data = new \stdClass();
        $data->delivery_type = 'shipping';
        $data->shipping_address = '';
        
        $v = new DataVerify($data);
        $v->field('shipping_address')
            ->when('delivery_type', '=', 'shipping')
            ->then->required;
        
        $this->assertFalse($v->verify());
        $errors = $v->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals('shipping_address', $errors[0]['field']);
    }
    
    public function testMultipleFieldsWithOldSyntax(): void
    {
        $data = new \stdClass();
        $data->account_type = 'business';
        $data->has_vat = true;
        $data->company_name = '';
        $data->vat_number = '';
        
        $v = new DataVerify($data);
        $v->field('company_name')
                ->when('account_type', '=', 'business')
                ->then->required->string
            ->field('vat_number')
                ->when('has_vat', '=', true)
                ->then->required->string;
        
        $this->assertFalse($v->verify());
        $errors = $v->getErrors();
        
        // Both should fail
        $this->assertGreaterThanOrEqual(2, count($errors));
    }
    
    public function testAndConditionsWithOldSyntax(): void
    {
        $data = new \stdClass();
        $data->type = 'premium';
        $data->amount = 150;
        $data->discount_code = '';
        
        $v = new DataVerify($data);
        $v->field('discount_code')
            ->when('type', '=', 'premium')
            ->and('amount', '>', 100)
            ->then->required->string;
        
        $this->assertFalse($v->verify());
        $errors = $v->getErrors();
        $this->assertNotEmpty($errors);
    }
    
    public function testOrConditionsWithOldSyntax(): void
    {
        $data = new \stdClass();
        $data->country = 'BE';
        $data->vat_number = '';
        
        $v = new DataVerify($data);
        $v->field('vat_number')
            ->when('country', '=', 'FR')
            ->or('country', '=', 'BE')
            ->or('country', '=', 'DE')
            ->then->required->regex('/^[A-Z]{2}\d{9,11}$/');
        
        $this->assertFalse($v->verify());
        $this->assertNotEmpty($v->getErrors());
    }
    
    public function testNestedPathsWithOldSyntax(): void
    {
        $data = new \stdClass();
        $data->config = new \stdClass();
        $data->config->features = new \stdClass();
        $data->config->features->advanced = new \stdClass();
        $data->config->features->advanced->enabled = true;
        $data->config->features->advanced->api_key = '';
        
        $v = new DataVerify($data);
        $v->field('config')->required->object
            ->subfield('features')->required->object
                ->subfield('features', 'advanced')->required->object
                    ->subfield('features', 'advanced', 'api_key')
                    ->when('config.features.advanced.enabled', '=', true)
                    ->then->required->string;
        
        $this->assertFalse($v->verify());
        $errors = $v->getErrors();
        $this->assertNotEmpty($errors);
    }
    
    public function testMixingConditionalAndUnconditionalOldStyle(): void
    {
        $data = new \stdClass();
        $data->email = 'invalid-email';
        $data->newsletter = true;
        $data->phone = '';
        
        $v = new DataVerify($data);
        $v->field('email')
                ->required
                ->email
            ->field('phone')
                ->when('newsletter', '=', true)
                ->then->required;
        
        $this->assertFalse($v->verify());
        $errors = $v->getErrors();
        
        // Email should fail on email validation
        // Phone should fail on required (conditional triggered)
        $this->assertGreaterThanOrEqual(2, count($errors));
    }
    
    public function testConditionalNotTriggeredOldStyle(): void
    {
        $data = new \stdClass();
        $data->delivery_type = 'pickup';
        $data->shipping_address = '';
        
        $v = new DataVerify($data);
        $v->field('shipping_address')
            ->when('delivery_type', '=', 'shipping')
            ->then->required;
        
        $this->assertTrue($v->verify());
    }
    
    public function testGuardsStillWorkWithOldStyle(): void
    {
        $data = ['x' => 1];
        $v = new DataVerify($data);
        
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Incomplete conditional validation");
        
        // This should still throw because when() without then
        $v->field('a')->when('x', '=', 1)->required();
    }
    
    public function testThenWithoutWhenStillThrows(): void
    {
        $data = ['field' => 'value'];
        $v = new DataVerify($data);
        
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Cannot use 'then' without 'when'");
        
        $v->field('field')->then->required;
    }
    
    public function testCannotMixAndOrStillEnforced(): void
    {
        $data = ['a' => 1, 'b' => 2, 'field' => 'value'];
        $v = new DataVerify($data);
        
        $this->expectException(\LogicException::class);
        
        $v->field('field')
            ->when('a', '=', 1)
            ->and('b', '=', 2)
            ->or('a', '=', 3);  // Should throw
    }
    
    public function testInvalidOperatorStillRejected(): void
    {
        $data = ['x' => 1, 'field' => 'value'];
        $v = new DataVerify($data);
        
        $this->expectException(\InvalidArgumentException::class);
        
        $v->field('field')->when('x', 'INVALID_OP', 1);
    }
    
    public function testComplexNestedScenarioOldStyle(): void
    {
        $data = new \stdClass();
        $data->user = new \stdClass();
        $data->user->type = 'business';
        $data->user->country = 'FR';
        $data->user->vat_number = '';
        
        $v = new DataVerify($data);
        $v->field('user')->required->object
            ->subfield('vat_number')
            ->when('user.type', '=', 'business')
            ->and('user.country', 'in', ['FR', 'DE', 'IT'])
            ->then->required->string->minLength(9);
        
        $this->assertFalse($v->verify());
        $errors = $v->getErrors();
        $this->assertNotEmpty($errors);
    }
    
    public function testBatchModeStillWorksWithConditionals(): void
    {
        $data = new \stdClass();
        $data->x = 1;
        $data->field1 = null;
        $data->field2 = null;
        
        $v = new DataVerify($data);
        $v->field('field1')
                ->when('x', '=', 1)
                ->then->required
            ->field('field2')
                ->when('x', '=', 1)
                ->then->required;
        
        // Batch mode should collect all errors
        $this->assertFalse($v->verify(batch: true));
        $errors = $v->getErrors();
        $this->assertCount(2, $errors);
    }
    
    public function testFailFastModeStillWorksWithConditionals(): void
    {
        $data = new \stdClass();
        $data->x = 1;
        $data->field1 = null;
        $data->field2 = null;
        
        $v = new DataVerify($data);
        $v->field('field1')
                ->when('x', '=', 1)
                ->then->required
            ->field('field2')
                ->when('x', '=', 1)
                ->then->required;
        
        // Fail-fast should stop at first error
        $this->assertFalse($v->verify(batch: false));
        $errors = $v->getErrors();
        $this->assertCount(1, $errors);
    }
}
