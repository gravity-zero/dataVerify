<?php

use PHPUnit\Framework\TestCase;
use Gravity\DataVerify;
use Gravity\Interfaces\ValidationStrategyInterface;

class CustomStrategy extends TestCase
{
    public function testRegisterAndUseStrategy()
    {
        $strategy = new class implements ValidationStrategyInterface {
            public function execute(mixed $value, array $args): bool {
                if (!is_string($value)) {
                    return false;
                }
                return $value === strrev($value);
            }
            
            public function getName(): string {
                return 'isPalindrome';
            }
        };

        $data = new stdClass();
        $data->word = "radar";

        $verifier = new DataVerify($data);
        $verifier->registerStrategy($strategy);
        $verifier->field("word")->isPalindrome();

        $this->assertTrue($verifier->verify());
    }

    public function testStrategyWithParameters()
    {
        $strategy = new class implements ValidationStrategyInterface {
            public function execute(mixed $value, array $args): bool {
                if (!is_numeric($value)) {
                    return false;
                }
                $factor = $args[0] ?? 1;
                return $value % $factor === 0;
            }
            
            public function getName(): string {
                return 'isMultipleOf';
            }
        };

        $data = new stdClass();
        $data->number = 12;

        $verifier = new DataVerify($data);
        $verifier->registerStrategy($strategy);
        $verifier->field("number")->isMultipleOf(3);

        $this->assertTrue($verifier->verify());
    }

    public function testStrategyFailsValidation()
    {
        $strategy = new class implements ValidationStrategyInterface {
            public function execute(mixed $value, array $args): bool {
                return is_int($value) && $value % 2 === 0;
            }
            
            public function getName(): string {
                return 'even';
            }
        };

        $data = new stdClass();
        $data->number = 5;

        $verifier = new DataVerify($data);
        $verifier->registerStrategy($strategy);
        $verifier->field('number')->even();

        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        $this->assertEquals('even', $errors[0]['test']);
    }

    public function testMultipleStrategiesCanBeRegistered()
    {
        $evenStrategy = new class implements ValidationStrategyInterface {
            public function execute(mixed $value, array $args): bool {
                return is_int($value) && $value % 2 === 0;
            }
            public function getName(): string {
                return 'even';
            }
        };

        $positiveStrategy = new class implements ValidationStrategyInterface {
            public function execute(mixed $value, array $args): bool {
                return is_numeric($value) && $value > 0;
            }
            public function getName(): string {
                return 'positive';
            }
        };

        $data = new stdClass();
        $data->number = 4;

        $verifier = new DataVerify($data);
        $verifier->registerStrategy($evenStrategy);
        $verifier->registerStrategy($positiveStrategy);
        $verifier->field('number')->even()->positive();

        $this->assertTrue($verifier->verify());
    }

    public function testStrategyWithComplexLogic()
    {
        $strategy = new class implements ValidationStrategyInterface {
            private array $forbiddenWords = ['spam', 'admin', 'root'];
            
            public function execute(mixed $value, array $args): bool {
                if (!is_string($value)) {
                    return false;
                }
                
                $lower = strtolower($value);
                foreach ($this->forbiddenWords as $word) {
                    if (str_contains($lower, $word)) {
                        return false;
                    }
                }
                
                return true;
            }
            
            public function getName(): string {
                return 'no_forbidden_words';
            }
        };

        $data = new stdClass();
        $data->username = "john_doe";

        $verifier = new DataVerify($data);
        $verifier->registerStrategy($strategy);
        $verifier->field('username')->no_forbidden_words();

        $this->assertTrue($verifier->verify());
    }
}