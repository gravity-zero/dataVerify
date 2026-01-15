<?php

use Gravity\DataVerify;
use Gravity\Registry\{SchemaRegistry, RuleSetRegistry, GlobalStrategyRegistry};
use Gravity\Validations\ValidationStrategy;
use PHPUnit\Framework\TestCase;

class SchemaWithCustomStrategyTest extends TestCase
{
    protected function setUp(): void
    {
        SchemaRegistry::reset();
        RuleSetRegistry::reset();
        GlobalStrategyRegistry::reset();
    }

    protected function tearDown(): void
    {
        SchemaRegistry::reset();
        RuleSetRegistry::reset();
        GlobalStrategyRegistry::reset();
    }

    public function testSchemaWithGlobalCustomStrategy(): void
    {
        $ibanStrategy = new class extends ValidationStrategy {
            public function getName(): string
            {
                return 'iban';
            }

            protected function handler(mixed $value): bool
            {
                // Simplified IBAN check: 2 letters + 2 digits + 10-30 alphanumeric
                return is_string($value) && preg_match('/^FR\d{25}$/', $value);
            }
        };

        DataVerify::global()->register($ibanStrategy);

        DataVerify::registerSchema('bankAccount')
            ->field('account')->required->array
                ->subfield('iban')->required->iban
                ->subfield('bic')->required->string;

        $data = [
            'account' => [
                'iban' => 'FR7630006000011234567890189',
                'bic' => 'BNPAFRPP'
            ]
        ];

        $dv = new DataVerify($data);
        $dv->schema('bankAccount');
        $validation = $dv->verify();
        var_dump($dv->getErrors());

        $this->assertTrue($validation);
    }

    public function testSchemaWithCustomStrategyFailure(): void
    {
        $ibanStrategy = new class extends ValidationStrategy {
            public function getName(): string
            {
                return 'iban';
            }

            protected function handler(mixed $value): bool
            {
                return is_string($value) && preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{10,30}$/', $value);
            }
        };

        DataVerify::global()->register($ibanStrategy);

        DataVerify::registerSchema('bankAccount')
            ->field('account')->required->object
                ->subfield('iban')->required->iban;

        $data = [
            'account' => [
                'iban' => 'invalid-iban'
            ]
        ];

        $dv = new DataVerify($data);
        $dv->schema('bankAccount');

        $this->assertFalse($dv->verify());
        
        $errors = $dv->getErrors();
        // At least the iban validation should fail
        $this->assertGreaterThanOrEqual(1, count($errors));
        
        $ibanErrors = array_filter($errors, fn($e) => $e['test'] === 'iban');
        $this->assertNotEmpty($ibanErrors);
    }

    public function testSchemaWithCustomStrategyWithParameters(): void
    {
        $currencyStrategy = new class extends ValidationStrategy {
            public function getName(): string
            {
                return 'currency';
            }

            protected function handler(mixed $value, array $allowed = ['EUR', 'USD', 'GBP']): bool
            {
                return is_string($value) && in_array($value, $allowed, true);
            }
        };

        DataVerify::global()->register($currencyStrategy);

        DataVerify::registerSchema('payment')
            ->field('amount')->required->numeric
            ->field('currency')->required->currency(['EUR', 'USD']);

        // Valid currency
        $data1 = ['amount' => 100, 'currency' => 'EUR'];
        $dv1 = new DataVerify($data1);
        $dv1->schema('payment');
        $this->assertTrue($dv1->verify());

        // Invalid currency (GBP not in allowed list)
        SchemaRegistry::reset();
        DataVerify::registerSchema('payment')
            ->field('amount')->required->numeric
            ->field('currency')->required->currency(['EUR', 'USD']);

        $data2 = ['amount' => 100, 'currency' => 'GBP'];
        $dv2 = new DataVerify($data2);
        $dv2->schema('payment');
        $this->assertFalse($dv2->verify());
    }

    public function testSchemaWithCustomStrategyAndConditionals(): void
    {
        $premiumValidator = new class extends ValidationStrategy {
            public function getName(): string
            {
                return 'premiumEmail';
            }

            protected function handler(mixed $value): bool
            {
                // Premium users must have company email (no gmail, yahoo, etc.)
                $freeDomains = ['gmail.com', 'yahoo.com', 'hotmail.com'];
                if (!is_string($value) || !str_contains($value, '@')) {
                    return false;
                }
                $domain = substr($value, strpos($value, '@') + 1);
                return !in_array($domain, $freeDomains, true);
            }
        };

        DataVerify::global()->register($premiumValidator);

        DataVerify::registerSchema('userRegistration')
            ->field('email')->required->email
                ->when('plan', '=', 'premium')
                    ->then->premiumEmail
            ->field('plan')->required->string;

        // Free plan - any email OK
        $data1 = ['email' => 'user@gmail.com', 'plan' => 'free'];
        $dv1 = new DataVerify($data1);
        $dv1->schema('userRegistration');
        $this->assertTrue($dv1->verify());

        // Premium plan with free email - should fail
        SchemaRegistry::reset();
        DataVerify::registerSchema('userRegistration')
            ->field('email')->required->email
                ->when('plan', '=', 'premium')
                    ->then->premiumEmail
            ->field('plan')->required->string;

        $data2 = ['email' => 'user@gmail.com', 'plan' => 'premium'];
        $dv2 = new DataVerify($data2);
        $dv2->schema('userRegistration');
        $this->assertFalse($dv2->verify());

        // Premium plan with company email - should pass
        SchemaRegistry::reset();
        DataVerify::registerSchema('userRegistration')
            ->field('email')->required->email
                ->when('plan', '=', 'premium')
                    ->then->premiumEmail
            ->field('plan')->required->string;

        $data3 = ['email' => 'user@company.com', 'plan' => 'premium'];
        $dv3 = new DataVerify($data3);
        $dv3->schema('userRegistration');
        $this->assertTrue($dv3->verify());
    }

    public function testSchemaWithRuleContainingCustomStrategy(): void
    {
        $vatStrategy = new class extends ValidationStrategy {
            public function getName(): string
            {
                return 'vatNumber';
            }

            protected function handler(mixed $value): bool
            {
                // Simplified EU VAT format check: 2 letters + 8-12 digits
                return is_string($value) && preg_match('/^[A-Z]{2}[0-9]{8,12}$/', $value);
            }
        };

        DataVerify::global()->register($vatStrategy);

        // Rule using custom strategy
        DataVerify::registerRules('europeanCompany')
            ->required
            ->string
            ->vatNumber;

        // Schema using the rule
        DataVerify::registerSchema('invoice')
            ->field('company')->required->array
                ->subfield('name')->required->string
                ->subfield('vat')->rule('europeanCompany');

        $data = [
            'company' => [
                'name' => 'ACME Corp',
                'vat' => 'FR123456789012'
            ]
        ];

        $dv = new DataVerify($data);
        $dv->schema('invoice');

        $this->assertTrue($dv->verify());
    }

    public function testSchemaWithMultipleCustomStrategies(): void
    {
        $slugStrategy = new class extends ValidationStrategy {
            public function getName(): string
            {
                return 'slug';
            }

            protected function handler(mixed $value): bool
            {
                return is_string($value) && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value);
            }
        };

        $hexColorStrategy = new class extends ValidationStrategy {
            public function getName(): string
            {
                return 'hexColor';
            }

            protected function handler(mixed $value): bool
            {
                return is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value);
            }
        };

        DataVerify::global()->register($slugStrategy);
        DataVerify::global()->register($hexColorStrategy);

        DataVerify::registerSchema('category')
            ->field('name')->required->string
            ->field('slug')->required->slug
            ->field('color')->required->hexColor;

        // Valid data
        $data1 = [
            'name' => 'Electronics',
            'slug' => 'electronics-and-gadgets',
            'color' => '#FF5733'
        ];
        $dv1 = new DataVerify($data1);
        $dv1->schema('category');
        $this->assertTrue($dv1->verify());

        // Invalid slug
        SchemaRegistry::reset();
        DataVerify::registerSchema('category')
            ->field('name')->required->string
            ->field('slug')->required->slug
            ->field('color')->required->hexColor;

        $data2 = [
            'name' => 'Electronics',
            'slug' => 'Invalid Slug!',
            'color' => '#FF5733'
        ];
        $dv2 = new DataVerify($data2);
        $dv2->schema('category');
        $this->assertFalse($dv2->verify());
    }

    public function testSchemaWithNestedSubfieldsAndCustomStrategy(): void
    {
        $phoneStrategy = new class extends ValidationStrategy {
            public function getName(): string
            {
                return 'frenchPhone';
            }

            protected function handler(mixed $value): bool
            {
                return is_string($value) && preg_match('/^0[1-9][0-9]{8}$/', $value);
            }
        };

        DataVerify::global()->register($phoneStrategy);

        DataVerify::registerSchema('contact')
            ->field('person')->required->array
                ->subfield('name')->required->string
                ->subfield('phone')->required->frenchPhone;

        $data = [
            'person' => [
                'name' => 'Jean Dupont',
                'phone' => '0612345678'
            ]
        ];

        $dv = new DataVerify($data);
        $dv->schema('contact');

        $this->assertTrue($dv->verify());
    }
}