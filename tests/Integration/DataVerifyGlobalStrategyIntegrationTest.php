<?php

use Gravity\DataVerify;
use Gravity\Registry\GlobalStrategyRegistry;
use Gravity\Interfaces\ValidationStrategyInterface;
use PHPUnit\Framework\TestCase;

class DataVerifyGlobalStrategyIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        GlobalStrategyRegistry::reset();
    }

    protected function tearDown(): void
    {
        GlobalStrategyRegistry::reset();
    }

    public function testGlobalStrategyAvailableInDataVerifyInstance(): void
    {
        // Register a global strategy
        $strategy = new class implements ValidationStrategyInterface {
            public function getName(): string { 
                return 'even_number'; 
            }
            
            public function execute(mixed $value, array $args): bool {
                return is_int($value) && $value % 2 === 0;
            }
        };

        DataVerify::global()->register($strategy);

        // Use in DataVerify instance
        $data = new stdClass();
        $data->number = 5; // Odd number - should fail

        $verifier = new DataVerify($data);
        $verifier->field('number')->even_number;

        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals('number', $errors[0]['field']);
        $this->assertEquals('even_number', $errors[0]['test']);
    }

    public function testGlobalStrategyPassesValidation(): void
    {
        $strategy = new class implements ValidationStrategyInterface {
            public function getName(): string { 
                return 'positive'; 
            }
            
            public function execute(mixed $value, array $args): bool {
                return is_numeric($value) && $value > 0;
            }
        };

        DataVerify::global()->register($strategy);

        $data = new stdClass();
        $data->value = 10; // Positive - should pass

        $verifier = new DataVerify($data);
        $verifier->field('value')->positive;

        $this->assertTrue($verifier->verify());
        $this->assertCount(0, $verifier->getErrors());
    }

    public function testGlobalStrategyAvailableInMultipleInstances(): void
    {
        $strategy = new class implements ValidationStrategyInterface {
            public function getName(): string { 
                return 'uppercase'; 
            }
            
            public function execute(mixed $value, array $args): bool {
                return is_string($value) && $value === strtoupper($value);
            }
        };

        DataVerify::global()->register($strategy);

        // First instance
        $data1 = new stdClass();
        $data1->code = 'lowercase';
        
        $verifier1 = new DataVerify($data1);
        $verifier1->field('code')->uppercase;
        
        $this->assertFalse($verifier1->verify());

        // Second instance - strategy still available
        $data2 = new stdClass();
        $data2->code = 'UPPERCASE';
        
        $verifier2 = new DataVerify($data2);
        $verifier2->field('code')->uppercase;
        
        $this->assertTrue($verifier2->verify());
    }

    public function testMultipleGlobalStrategiesInSameValidation(): void
    {
        $strategy1 = new class implements ValidationStrategyInterface {
            public function getName(): string { return 'min_value'; }
            
            public function execute(mixed $value, array $args): bool {
                $min = $args[0] ?? 0;
                return is_numeric($value) && $value >= $min;
            }
        };

        $strategy2 = new class implements ValidationStrategyInterface {
            public function getName(): string { return 'max_value'; }
            
            public function execute(mixed $value, array $args): bool {
                $max = $args[0] ?? 100;
                return is_numeric($value) && $value <= $max;
            }
        };

        DataVerify::global()->registerMultiple([$strategy1, $strategy2]);

        $data = new stdClass();
        $data->age = 25;

        $verifier = new DataVerify($data);
        $verifier
            ->field('age')->min_value(18)
            ->field('age')->max_value(65);

        $this->assertTrue($verifier->verify());
    }

    public function testGlobalStrategyWithArguments(): void
    {
        $strategy = new class implements ValidationStrategyInterface {
            public function getName(): string { 
                return 'divisible_by'; 
            }
            
            public function execute(mixed $value, array $args): bool {
                $divisor = $args[0] ?? 1;
                return is_int($value) && $value % $divisor === 0;
            }
        };

        DataVerify::global()->register($strategy);

        $data = new stdClass();
        $data->number = 15;

        $verifier = new DataVerify($data);
        $verifier->field('number')->divisible_by(5);

        $this->assertTrue($verifier->verify());

        // Test with failing case
        $data2 = new stdClass();
        $data2->number = 15;

        $verifier2 = new DataVerify($data2);
        $verifier2->field('number')->divisible_by(7);

        $this->assertFalse($verifier2->verify());
    }

    public function testInstanceStrategyDoesNotPollute(): void
    {
        // Global strategy
        $globalStrategy = new class implements ValidationStrategyInterface {
            public function getName(): string { return 'global_test'; }
            public function execute(mixed $value, array $args): bool { return true; }
        };

        DataVerify::global()->register($globalStrategy);

        // Instance-specific strategy
        $instanceStrategy = new class implements ValidationStrategyInterface {
            public function getName(): string { return 'instance_test'; }
            public function execute(mixed $value, array $args): bool { return false; }
        };

        $data1 = new stdClass();
        $data1->value = 'test';
        
        $verifier1 = new DataVerify($data1);
        $verifier1->registerStrategy($instanceStrategy); // Instance only
        $verifier1->field('value')->global_test; // Global - should work
        $verifier1->field('value')->instance_test; // Instance - should work

        // Second instance should have global but NOT instance_test
        $data2 = new stdClass();
        $data2->value = 'test';
        
        $verifier2 = new DataVerify($data2);
        $verifier2->field('value')->global_test; // Should work
        
        $this->expectException(\Gravity\Exceptions\ValidationTestNotFoundException::class);
        $verifier2->field('value')->instance_test; // Should throw
    }

    public function testMixGlobalAndInstanceStrategies(): void
    {
        // Global
        $globalStrategy = new class implements ValidationStrategyInterface {
            public function getName(): string { return 'not_empty'; }
            
            public function execute(mixed $value, array $args): bool {
                return !empty($value);
            }
        };

        DataVerify::global()->register($globalStrategy);

        // Instance
        $instanceStrategy = new class implements ValidationStrategyInterface {
            public function getName(): string { return 'custom_format'; }
            
            public function execute(mixed $value, array $args): bool {
                return is_string($value) && preg_match('/^[A-Z]{3}-\d{3}$/', $value);
            }
        };

        $data = new stdClass();
        $data->code = 'ABC-123';
        
        $verifier = new DataVerify($data);
        $verifier->registerStrategy($instanceStrategy);
        $verifier
            ->field('code')->not_empty       // Global
            ->field('code')->custom_format;  // Instance

        $this->assertTrue($verifier->verify());
    }

    public function testLoadFromDirectoryIntegration(): void
    {
        DataVerify::global()->loadFromDirectory(
            path: __DIR__ . '/../fixtures/strategies',
            namespace: 'Tests\\Fixtures\\Strategies'
        );

        // ValidStrategy exists in fixtures (validates value === 'valid')
        $data = new stdClass();
        $data->test = 'valid';
        
        $verifier = new DataVerify($data);
        $verifier->field('test')->valid_fixture;
        
        $this->assertTrue($verifier->verify());

        // Test with invalid value
        $data2 = new stdClass();
        $data2->test = 'invalid';
        
        $verifier2 = new DataVerify($data2);
        $verifier2->field('test')->valid_fixture;
        
        $this->assertFalse($verifier2->verify());
    }

    public function testGlobalStrategyWithSubfields(): void
    {
        $strategy = new class implements ValidationStrategyInterface {
            public function getName(): string { return 'non_zero'; }
            
            public function execute(mixed $value, array $args): bool {
                return is_numeric($value) && $value != 0;
            }
        };

        DataVerify::global()->register($strategy);

        $data = new stdClass();
        $data->user = new stdClass();
        $data->user->balance = 0;

        $verifier = new DataVerify($data);
        $verifier
            ->field('user')->required->object
                ->subfield('balance')->required->non_zero;

        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals('user.balance', $errors[0]['field']);
        $this->assertEquals('non_zero', $errors[0]['test']);
    }

    public function testGlobalStrategyWithConditionalValidation(): void
    {
        $strategy = new class implements ValidationStrategyInterface {
            public function getName(): string { return 'strong_password'; }
            
            public function execute(mixed $value, array $args): bool {
                return is_string($value) 
                    && strlen($value) >= 12
                    && preg_match('/[A-Z]/', $value)
                    && preg_match('/[0-9]/', $value)
                    && preg_match('/[^A-Za-z0-9]/', $value);
            }
        };

        DataVerify::global()->register($strategy);

        $data = new stdClass();
        $data->account_type = 'admin';
        $data->password = 'weak';

        $verifier = new DataVerify($data);
        $verifier
            ->field('password')
            ->when('account_type', '=', 'admin')
            ->then->required->strong_password;

        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        $this->assertEquals('strong_password', $errors[0]['test']);
    }

    public function testGlobalStrategyWithBatchMode(): void
    {
        $strategy = new class implements ValidationStrategyInterface {
            public function getName(): string { return 'alphabetic'; }
            
            public function execute(mixed $value, array $args): bool {
                return is_string($value) && ctype_alpha($value);
            }
        };

        DataVerify::global()->register($strategy);

        $data = new stdClass();
        $data->field1 = 'abc123'; // Invalid
        $data->field2 = '456xyz'; // Invalid
        $data->field3 = 'validtext'; // Valid

        $verifier = new DataVerify($data);
        $verifier
            ->field('field1')->alphabetic
            ->field('field2')->alphabetic
            ->field('field3')->alphabetic;

        $this->assertFalse($verifier->verify(batch: true));
        
        $errors = $verifier->getErrors();
        $this->assertCount(2, $errors); // field1 and field2 should fail
    }

    public function testGlobalStrategyWithFailFastMode(): void
    {
        $strategy = new class implements ValidationStrategyInterface {
            public function getName(): string { return 'lowercase'; }
            
            public function execute(mixed $value, array $args): bool {
                return is_string($value) && $value === strtolower($value);
            }
        };

        DataVerify::global()->register($strategy);

        $data = new stdClass();
        $data->field1 = 'UPPERCASE'; // Invalid - should stop here
        $data->field2 = 'UPPERCASE'; // Won't be validated

        $verifier = new DataVerify($data);
        $verifier
            ->field('field1')->lowercase
            ->field('field2')->lowercase;

        $this->assertFalse($verifier->verify(batch: false));
        
        $errors = $verifier->getErrors();
        $this->assertCount(1, $errors); // Only field1 should fail (fail-fast)
    }

    public function testFluentRegistrationAndUsage(): void
    {
        $s1 = new class implements ValidationStrategyInterface {
            public function getName(): string { return 's1'; }
            public function execute(mixed $value, array $args): bool { return true; }
        };

        $s2 = new class implements ValidationStrategyInterface {
            public function getName(): string { return 's2'; }
            public function execute(mixed $value, array $args): bool { return true; }
        };

        // Fluent registration
        DataVerify::global()
            ->register($s1)
            ->register($s2);

        $this->assertEquals(2, DataVerify::global()->count());

        // Use both in validation
        $data = new stdClass();
        $data->test = 'value';

        $verifier = new DataVerify($data);
        $verifier
            ->field('test')->s1
            ->field('test')->s2;

        $this->assertTrue($verifier->verify());
    }

    public function testGlobalStrategyErrorMessages(): void
    {
        $strategy = new class implements ValidationStrategyInterface {
            public function getName(): string { return 'custom_rule'; }
            public function execute(mixed $value, array $args): bool { return false; }
        };

        DataVerify::global()->register($strategy);

        $data = new stdClass();
        $data->field = 'test';

        $verifier = new DataVerify($data);
        $verifier->field('field')->custom_rule->errorMessage('Custom error message');

        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        $this->assertEquals('Custom error message', $errors[0]['message']);
    }
}