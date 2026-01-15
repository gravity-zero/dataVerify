<?php

use Gravity\DataVerify;
use Gravity\Rules\{RuleSet, RuleSetBuilder};
use Gravity\Registry\RuleSetRegistry;
use PHPUnit\Framework\TestCase;

class RuleSetTest extends TestCase
{
    protected function setUp(): void
    {
        RuleSetRegistry::reset();
    }

    public function testRuleSetCreation(): void
    {
        $ruleSet = new RuleSet('test');
        
        $this->assertEquals('test', $ruleSet->getName());
        $this->assertEmpty($ruleSet->getValidations());
    }

    public function testRuleSetWithValidations(): void
    {
        $ruleSet = new RuleSet('strongPassword');
        $ruleSet->addValidation('minLength', [12])
                ->addValidation('containsUpper', [])
                ->addValidation('containsLower', []);

        $validations = $ruleSet->getValidations();
        
        $this->assertCount(3, $validations);
        $this->assertEquals('minLength', $validations[0]['name']);
        $this->assertEquals([12], $validations[0]['args']);
    }

    public function testRuleSetBuilder(): void
    {
        $builder = new RuleSetBuilder('strongPassword');
        $builder->minLength(12)->containsUpper->containsLower;
        
        $ruleSet = $builder->getRuleSet();
        
        $this->assertEquals('strongPassword', $ruleSet->getName());
        $this->assertCount(3, $ruleSet->getValidations());
    }

    public function testRuleSetRegistration(): void
    {
        DataVerify::registerRules('strongPassword')
            ->minLength(12)
            ->containsUpper;

        $registry = RuleSetRegistry::instance();
        
        $this->assertTrue($registry->has('strongPassword'));
        $this->assertInstanceOf(RuleSet::class, $registry->get('strongPassword'));
    }

    public function testCannotRegisterSameRuleTwice(): void
    {
        DataVerify::registerRules('strongPassword')->minLength(12);
        
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Rule 'strongPassword' is already registered");

        DataVerify::registerRules('strongPassword')->containsUpper;
    }

    public function testApplyRuleWithMethodSyntax(): void
    {
        DataVerify::registerRules('strongPassword')
            ->minLength(12)
            ->containsUpper
            ->containsLower;

        $data = ['password' => 'weak'];
        
        $dv = new DataVerify($data);
        $dv->field('password')->rule('strongPassword');
        
        $this->assertFalse($dv->verify());
    }

    public function testApplyRuleWithPropertySyntax(): void
    {
        DataVerify::registerRules('strongPassword')
            ->minLength(12)
            ->containsUpper;

        $data = ['password' => 'weak'];
        
        $dv = new DataVerify($data);
        $dv->field('password')->rule->strongPassword;
        
        $this->assertFalse($dv->verify());
    }

    public function testChainMultipleRules(): void
    {
        DataVerify::registerRules('strong')->minLength(12);
        DataVerify::registerRules('secure')->containsUpper->containsLower;

        $data = ['password' => 'weakpass'];
        
        $dv = new DataVerify($data);
        $dv->field('password')
            ->rule('strong')
            ->rule('secure');
        
        $this->assertFalse($dv->verify());
        
        $errors = $dv->getErrors();
        $this->assertGreaterThanOrEqual(2, count($errors));
    }

    public function testRuleNotFound(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Rule 'nonexistent' not found");

        $data = ['password' => 'test'];
        
        $dv = new DataVerify($data);
        $dv->field('password')->rule('nonexistent');
    }

    public function testMixRuleWithInlineValidations(): void
    {
        DataVerify::registerRules('strong')->minLength(12)->containsUpper;

        $data = ['password' => 'WeakPass'];
        
        $dv = new DataVerify($data);
        $dv->field('password')
            ->required
            ->rule('strong')
            ->containsSpecialCharacter;
        
        $this->assertFalse($dv->verify());
    }

    public function testInvalidValidationInRule(): void
    {
        $this->expectException(\Gravity\Exceptions\ValidationTestNotFoundException::class);

        DataVerify::registerRules('invalid')->nonexistentValidation();
    }
}
