<?php

use Gravity\DataVerify;
use Gravity\Registry\{RuleSetRegistry, GlobalStrategyRegistry};
use Gravity\Interfaces\ValidationStrategyInterface;
use Gravity\Validations\ValidationStrategy;
use Gravity\Attributes\ValidationRule;
use PHPUnit\Framework\TestCase;

class RuleSetWithCustomStrategyTest extends TestCase
{
    protected function setUp(): void
    {
        RuleSetRegistry::reset();
        GlobalStrategyRegistry::reset();
    }

    protected function tearDown(): void
    {
        RuleSetRegistry::reset();
        GlobalStrategyRegistry::reset();
    }

    public function testRuleWithGlobalCustomStrategy(): void
    {
        // Register a custom strategy globally
        $siretStrategy = new class extends ValidationStrategy {
            public function getName(): string
            {
                return 'siret';
            }

            protected function handler(mixed $value): bool
            {
                return is_string($value) && preg_match('/^\d{14}$/', $value);
            }
        };

        DataVerify::global()->register($siretStrategy);

        // Create a rule that uses the custom strategy
        DataVerify::registerRules('frenchCompany')
            ->required
            ->siret;

        $data = ['company_id' => '12345678901234'];

        $dv = new DataVerify($data);
        $dv->field('company_id')->rule('frenchCompany');

        $this->assertTrue($dv->verify());
    }

    public function testRuleWithGlobalCustomStrategyFailure(): void
    {
        $siretStrategy = new class extends ValidationStrategy {
            public function getName(): string
            {
                return 'siret';
            }

            protected function handler(mixed $value): bool
            {
                return is_string($value) && preg_match('/^\d{14}$/', $value);
            }
        };

        DataVerify::global()->register($siretStrategy);

        DataVerify::registerRules('frenchCompany')
            ->required
            ->siret;

        $data = ['company_id' => 'invalid'];

        $dv = new DataVerify($data);
        $dv->field('company_id')->rule('frenchCompany');

        $this->assertFalse($dv->verify());
        
        $errors = $dv->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals('siret', $errors[0]['test']);
    }

    public function testRuleWithCustomStrategyWithParameters(): void
    {
        $rangeStrategy = new class extends ValidationStrategy {
            public function getName(): string
            {
                return 'customRange';
            }

            protected function handler(mixed $value, int $min = 0, int $max = 100): bool
            {
                return is_numeric($value) && $value >= $min && $value <= $max;
            }
        };

        DataVerify::global()->register($rangeStrategy);

        DataVerify::registerRules('percentageRule')
            ->required
            ->customRange(0, 100);

        // Valid percentage
        $data1 = ['percentage' => 50];
        $dv1 = new DataVerify($data1);
        $dv1->field('percentage')->rule('percentageRule');
        $this->assertTrue($dv1->verify());

        // Invalid percentage
        RuleSetRegistry::reset();
        DataVerify::registerRules('percentageRule')
            ->required
            ->customRange(0, 100);

        $data2 = ['percentage' => 150];
        $dv2 = new DataVerify($data2);
        $dv2->field('percentage')->rule('percentageRule');
        $this->assertFalse($dv2->verify());
    }

    public function testRuleCombiningBuiltInAndCustomStrategies(): void
    {
        $customStrategy = new class extends ValidationStrategy {
            public function getName(): string
            {
                return 'startsWithPrefix';
            }

            protected function handler(mixed $value, string $prefix = 'PRE'): bool
            {
                return is_string($value) && str_starts_with($value, $prefix);
            }
        };

        DataVerify::global()->register($customStrategy);

        DataVerify::registerRules('productCode')
            ->required
            ->string
            ->minLength(10)
            ->startsWithPrefix('PROD-');

        // Valid product code
        $data1 = ['code' => 'PROD-123456'];
        $dv1 = new DataVerify($data1);
        $dv1->field('code')->rule('productCode');
        $this->assertTrue($dv1->verify());

        // Invalid - wrong prefix
        RuleSetRegistry::reset();
        DataVerify::registerRules('productCode')
            ->required
            ->string
            ->minLength(10)
            ->startsWithPrefix('PROD-');

        $data2 = ['code' => 'CODE-123456'];
        $dv2 = new DataVerify($data2);
        $dv2->field('code')->rule('productCode');
        $this->assertFalse($dv2->verify());
    }

    public function testRuleWithUnregisteredCustomStrategyThrows(): void
    {
        $this->expectException(\Gravity\Exceptions\ValidationTestNotFoundException::class);

        // Try to use a custom strategy that's not registered
        DataVerify::registerRules('invalid')
            ->nonExistentCustomStrategy;
    }

    public function testMultipleRulesWithDifferentCustomStrategies(): void
    {
        $strategy1 = new class extends ValidationStrategy {
            public function getName(): string
            {
                return 'isEven';
            }

            protected function handler(mixed $value): bool
            {
                return is_numeric($value) && $value % 2 === 0;
            }
        };

        $strategy2 = new class extends ValidationStrategy {
            public function getName(): string
            {
                return 'isPositive';
            }

            protected function handler(mixed $value): bool
            {
                return is_numeric($value) && $value > 0;
            }
        };

        DataVerify::global()->register($strategy1);
        DataVerify::global()->register($strategy2);

        DataVerify::registerRules('evenNumber')->isEven;
        DataVerify::registerRules('positiveNumber')->isPositive;

        $data = ['value' => 4];

        $dv = new DataVerify($data);
        $dv->field('value')
            ->rule('evenNumber')
            ->rule('positiveNumber');

        $this->assertTrue($dv->verify());
    }
}