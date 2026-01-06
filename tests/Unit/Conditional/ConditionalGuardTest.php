<?php

use Gravity\DataVerify;
use PHPUnit\Framework\TestCase;

class ConditionalGuardTest extends TestCase
{
    public function test_field_throws_if_when_not_followed_by_then(): void
    {
        $v = new DataVerify(['x' => 1]);

        $this->expectException(\LogicException::class);
        $v->when('x', '=', 1)->field('a');
    }

    public function test_subfield_throws_if_when_not_followed_by_then(): void
    {
        $v = new DataVerify(['x' => 1]);

        $v->field('a');
        $this->expectException(\LogicException::class);

        $v->when('x', '=', 1)->subfield('child');
    }

    public function test_validation_call_throws_if_when_not_followed_by_then(): void
    {
        $v = new DataVerify(['x' => 1]);

        $v->field('a');

        $this->expectException(\LogicException::class);
        $v->when('x', '=', 1)->required();
    }

    public function test_then_mode_does_not_add_validation_when_condition_is_false(): void
    {
        $v = new DataVerify(['x' => 1, 'name' => null]);

        $v->field('name')
          ->when('x', '=', 2)
          ->then
          ->required();

        $ok = $v->verify(batch: true);
        $this->assertTrue($ok, 'Validation should be skipped when condition is false');
        $this->assertSame([], $v->getErrors(), 'No error expected because required() must not be added');
    }

    public function test_then_mode_adds_validation_when_condition_is_true(): void
    {
        $v = new DataVerify(['x' => 1, 'name' => null]);

        $v->field('name')
          ->when('x', '=', 1)
          ->then
          ->required();

        $ok = $v->verify(batch: true);
        $this->assertFalse($ok, 'Validation should run when condition is true');
        $this->assertNotSame([], $v->getErrors(), 'Error expected because required() must be added');
    }

    public function test_reset_is_called_on_field_and_clears_then_mode_for_next_validations(): void
    {
        $v = new DataVerify(['x' => 1, 'a' => null, 'b' => null]);

        $v->field('a')
          ->when('x', '=', 2)
          ->then
          ->required();

        $v->field('b')->required();

        $ok = $v->verify(batch: true);
        $this->assertFalse($ok, 'b.required() must run and fail because b is null');
        $this->assertNotSame([], $v->getErrors());
    }

    public function test_validation_name_is_used_to_run_the_expected_rule(): void
    {
        $v = new DataVerify(['name' => 'ab']);

        $v->field('name')->minLength(3);

        $ok = $v->verify(batch: true);
        $this->assertFalse($ok, 'minLength(3) must fail for "ab"');
        $this->assertNotSame([], $v->getErrors());
    }

    public function testFieldResetsConditionalEngineState(): void
    {
        $data = new \stdClass();
        $data->field1 = 'value';
        $data->field2 = 'other';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('field1')
            ->when('field1', '=', 'value')
            ->then->required
            ->field('field2')
            ->required;
        
        $this->assertTrue($verifier->verify());
    }

    public function testSubfieldResetsConditionalEngineState(): void
    {
        $data = new \stdClass();
        $data->parent = new \stdClass();
        $data->parent->sub1 = 'a';
        $data->parent->sub2 = 'b';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('parent')->required->object
                ->subfield('sub1')
                ->when('parent.sub1', '=', 'a')
                ->then->required
                ->subfield('sub2')
                ->required;
        
        $this->assertTrue($verifier->verify());
    }

    public function testThenReturnsThisForMethodChaining(): void
    {
        $data = new \stdClass();
        $data->field = 'test';
        
        $verifier = new DataVerify($data);
        $result = $verifier
            ->field('field')
            ->when('field', '=', 'test')
            ->then
            ->required();
        
        $this->assertInstanceOf(DataVerify::class, $result);
        $this->assertTrue($verifier->verify());
    }

    public function testWhenRejectsInvalidOperator(): void
    {
        $data = (object)['type' => 'test', 'field' => 'value'];
        $verifier = new DataVerify($data);
        
        $this->expectException(\InvalidArgumentException::class);
        $verifier->field('field')->when('type', 'INVALID', 'test');
    }

    public function testAndRejectsInvalidOperator(): void
    {
        $data = (object)['a' => 1, 'b' => 2, 'field' => 'value'];
        $verifier = new DataVerify($data);
        
        $this->expectException(\InvalidArgumentException::class);
        $verifier
            ->field('field')
            ->when('a', '=', 1)
            ->and('b', '<<<', 2);
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