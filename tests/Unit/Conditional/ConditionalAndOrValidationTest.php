<?php


use Gravity\DataVerify;
use PHPUnit\Framework\TestCase;

/**
 * Tests for AND/OR conditional logic
 */
class ConditionalAndOrValidationTest extends TestCase
{
    public function testAndConditionBothTrue(): void
    {
        $data = new stdClass();
        $data->type = 'premium';
        $data->amount = 150;
        $data->field = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('field')
            ->when('type', '=', 'premium')
            ->and('amount', '>', 100)
            ->then->required;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        $this->assertCount(1, $errors);
    }

    public function testAndConditionOneFalse(): void
    {
        $data = new stdClass();
        $data->type = 'premium';
        $data->amount = 50;
        $data->field = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('field')
            ->when('type', '=', 'premium')
            ->and('amount', '>', 100)
            ->then->required;
        
        $this->assertTrue($verifier->verify());
    }

    public function testAndConditionMultiple(): void
    {
        $data = new stdClass();
        $data->type = 'premium';
        $data->amount = 150;
        $data->country = 'FR';
        $data->field = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('field')
            ->when('type', '=', 'premium')
            ->and('amount', '>=', 100)
            ->and('country', '=', 'FR')
            ->then->required;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        $this->assertCount(1, $errors);
    }

    public function testAndConditionMultipleOneFalse(): void
    {
        $data = new stdClass();
        $data->type = 'premium';
        $data->amount = 150;
        $data->country = 'US';
        $data->field = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('field')
            ->when('type', '=', 'premium')
            ->and('amount', '>=', 100)
            ->and('country', '=', 'FR')
            ->then->required;
        
        $this->assertTrue($verifier->verify());
    }

    public function testOrConditionBothTrue(): void
    {
        $data = new stdClass();
        $data->country = 'FR';
        $data->field = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('field')
            ->when('country', '=', 'FR')
            ->or('country', '=', 'BE')
            ->then->required;
        
        $this->assertFalse($verifier->verify());
    }

    public function testOrConditionOneTrue(): void
    {
        $data = new stdClass();
        $data->country = 'BE';
        $data->field = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('field')
            ->when('country', '=', 'FR')
            ->or('country', '=', 'BE')
            ->then->required;
        
        $this->assertFalse($verifier->verify());
    }

    public function testOrConditionAllFalse(): void
    {
        $data = new stdClass();
        $data->country = 'DE';
        $data->field = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('field')
            ->when('country', '=', 'FR')
            ->or('country', '=', 'BE')
            ->then->required;
        
        $this->assertTrue($verifier->verify());
    }

    public function testOrConditionMultiple(): void
    {
        $data = new stdClass();
        $data->country = 'IT';
        $data->field = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('field')
            ->when('country', '=', 'FR')
            ->or('country', '=', 'BE')
            ->or('country', '=', 'IT')
            ->then->required;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        $this->assertCount(1, $errors);
    }

    public function testCannotMixAndOr(): void
    {
        $data = new stdClass();
        $data->x = 1;
        
        $verifier = new DataVerify($data);
        
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Cannot mix 'or' with 'and'");
        
        $verifier
            ->field('x')
            ->when('x', '=', 1)
            ->and('x', '>', 0)
            ->or('x', '<', 10);
    }

    public function testCannotUseAndWithoutWhen(): void
    {
        $data = new stdClass();
        $data->field = '';
        
        $verifier = new DataVerify($data);
        
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Start with when() first");
        
        $verifier
            ->field('field')
            ->and('x', '=', 1);
    }

    public function testCannotUseOrWithoutWhen(): void
    {
        $data = new stdClass();
        $data->field = '';
        
        $verifier = new DataVerify($data);
        
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Cannot use 'or' without 'when()'");
        
        $verifier
            ->field('field')
            ->or('x', '=', 1);
    }

    public function testCannotUseAndAfterThen(): void
    {
        $data = new stdClass();
        $data->type = 'premium';
        $data->field = '';
        
        $verifier = new DataVerify($data);
        
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Cannot use 'and' after 'then'");
        
        $verifier
            ->field('field')
            ->when('type', '=', 'premium')
            ->then
            ->and('x', '=', 1);
    }

    public function testCannotUseOrAfterThen(): void
    {
        $data = new stdClass();
        $data->type = 'premium';
        $data->field = '';
        
        $verifier = new DataVerify($data);
        
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Cannot use 'or' after 'then'");
        
        $verifier
            ->field('field')
            ->when('type', '=', 'premium')
            ->then
            ->or('x', '=', 1);
    }

    public function testComplexAndConditionWithSubfields(): void
    {
        $data = new stdClass();
        $data->user = new stdClass();
        $data->user->type = 'business';
        $data->user->country = 'FR';
        $data->user->vat_number = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('user')->required->object
                ->subfield('vat_number')
                ->when('user.type', '=', 'business')
                ->and('user.country', 'in', ['FR', 'DE', 'IT'])
                ->then->required->string;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals('user.vat_number', $errors[0]['field']);
    }

    public function testComplexOrConditionWithSubfields(): void
    {
        $data = new stdClass();
        $data->order = new stdClass();
        $data->order->type = 'express';
        $data->order->carrier = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('order')->required->object
                ->subfield('carrier')
                ->when('order.type', '=', 'express')
                ->or('order.type', '=', 'priority')
                ->then->required->string;
        
        $this->assertFalse($verifier->verify());
    }

    public function testAndConditionWithDifferentOperators(): void
    {
        $data = new stdClass();
        $data->age = 25;
        $data->country = 'FR';
        $data->income = 50000;
        $data->field = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('field')
            ->when('age', '>=', 18)
            ->and('country', 'in', ['FR', 'BE'])
            ->and('income', '>', 30000)
            ->then->required;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        $this->assertCount(1, $errors);
    }

    public function testOrConditionWithDifferentOperators(): void
    {
        $data = new stdClass();
        $data->status = 'pending';
        $data->field = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('field')
            ->when('status', '=', 'pending')
            ->or('status', '=', 'processing')
            ->or('status', '!=', 'completed')
            ->then->required;
        
        $this->assertFalse($verifier->verify());
    }

    public function testMultipleFieldsWithAndConditions(): void
    {
        $data = new stdClass();
        $data->type = 'business';
        $data->country = 'FR';
        $data->company_name = '';
        $data->vat_number = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('company_name')
            ->when('type', '=', 'business')
            ->and('country', 'in', ['FR', 'DE'])
            ->then->required->string
            
            ->field('vat_number')
            ->when('type', '=', 'business')
            ->and('country', '=', 'FR')
            ->then->required->regex('/^FR\d{11}$/');
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        $this->assertCount(2, $errors);
    }

    public function testAndConditionResetsBetweenFields(): void
    {
        $data = new stdClass();
        $data->type1 = 'A';
        $data->amount1 = 100;
        $data->type2 = 'B';
        $data->field1 = '';
        $data->field2 = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('field1')
            ->when('type1', '=', 'A')
            ->and('amount1', '>=', 100)
            ->then->required
            
            ->field('field2')
            ->when('type2', '=', 'C')
            ->then->required;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        
        $this->assertCount(1, $errors);
        $this->assertEquals('field1', $errors[0]['field']);
    }

    public function testNestedPathsWithAndConditions(): void
    {
        $data = new stdClass();
        $data->config = new stdClass();
        $data->config->level = 'advanced';
        $data->config->mode = 'production';
        $data->settings = new stdClass();
        $data->settings->api_key = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('settings')->required->object
                ->subfield('api_key')
                ->when('config.level', '=', 'advanced')
                ->and('config.mode', '=', 'production')
                ->then->required->string;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.api_key', $errors[0]['field']);
    }

    public function testMixAndWithNormalValidations(): void
    {
        $data = new stdClass();
        $data->type = 'premium';
        $data->amount = 200;
        $data->email = 'invalid';
        $data->discount = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('email')
            ->required
            ->email
            
            ->field('discount')
            ->when('type', '=', 'premium')
            ->and('amount', '>', 150)
            ->then->required;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        
        $this->assertCount(2, $errors);
    }

    public function testOrWithNormalValidations(): void
    {
        $data = new stdClass();
        $data->country = 'BE';
        $data->name = '';
        $data->vat = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('name')
            ->required
            
            ->field('vat')
            ->when('country', '=', 'FR')
            ->or('country', '=', 'BE')
            ->or('country', '=', 'DE')
            ->then->required;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        $this->assertCount(2, $errors);
    }

    public function testCannotMixAndWithOrAfterFirstOr(): void
    {
        $data = (object)['a' => 1, 'b' => 2, 'c' => 3, 'field' => 'x'];
        $verifier = new DataVerify($data);
        
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Cannot mix 'and' with 'or'");
        
        $verifier
            ->field('field')
            ->when('a', '=', 1)
            ->or('b', '=', 2)
            ->and('c', '=', 3);
    }

    public function testOrRejectsInvalidOperator(): void
    {
        $data = (object)['a' => 1, 'b' => 2, 'field' => 'value'];
        $verifier = new DataVerify($data);
        
        $this->expectException(\InvalidArgumentException::class);
        $verifier
            ->field('field')
            ->when('a', '=', 1)
            ->or('b', '>>>', 2);
    }
}