// tests/GlobalStrategyRegistryTest.php

<?php

use Gravity\Registry\GlobalStrategyRegistry;
use Gravity\Interfaces\ValidationStrategyInterface;
use PHPUnit\Framework\TestCase;

class GlobalStrategyRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        // Clean state before each test
        GlobalStrategyRegistry::reset();
    }

    protected function tearDown(): void
    {
        // Clean state after each test
        GlobalStrategyRegistry::reset();
    }

    public function testInstanceIsSingleton(): void
    {
        $instance1 = GlobalStrategyRegistry::instance();
        $instance2 = GlobalStrategyRegistry::instance();

        $this->assertSame($instance1, $instance2, 'Instance should be singleton');
    }

    public function testRegisterSingleStrategy(): void
    {
        $strategy = new class implements ValidationStrategyInterface {
            public function getName(): string { return 'test_strategy'; }
            public function execute(mixed $value, array $args): bool { return true; }
        };

        $registry = GlobalStrategyRegistry::instance();
        $result = $registry->register($strategy);

        $this->assertSame($registry, $result, 'register() should return self for chaining');
        $this->assertTrue($registry->has('test_strategy'));
        $this->assertEquals(1, $registry->count());
    }

    public function testRegisterMultipleStrategies(): void
    {
        $strategy1 = new class implements ValidationStrategyInterface {
            public function getName(): string { return 'strategy1'; }
            public function execute(mixed $value, array $args): bool { return true; }
        };

        $strategy2 = new class implements ValidationStrategyInterface {
            public function getName(): string { return 'strategy2'; }
            public function execute(mixed $value, array $args): bool { return true; }
        };

        $registry = GlobalStrategyRegistry::instance();
        $result = $registry->registerMultiple([$strategy1, $strategy2]);

        $this->assertSame($registry, $result, 'registerMultiple() should return self for chaining');
        $this->assertEquals(2, $registry->count());
        $this->assertTrue($registry->has('strategy1'));
        $this->assertTrue($registry->has('strategy2'));
    }

    public function testRegisterMultipleThrowsOnInvalidStrategy(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('All strategies must implement ValidationStrategyInterface');

        GlobalStrategyRegistry::instance()->registerMultiple([
            new stdClass() // Not a ValidationStrategyInterface
        ]);
    }

    public function testGetStrategy(): void
    {
        $strategy = new class implements ValidationStrategyInterface {
            public function getName(): string { return 'test'; }
            public function execute(mixed $value, array $args): bool { return true; }
        };

        $registry = GlobalStrategyRegistry::instance();
        $registry->register($strategy);

        $retrieved = $registry->get('test');
        $this->assertSame($strategy, $retrieved);
    }

    public function testGetNonExistentStrategy(): void
    {
        $registry = GlobalStrategyRegistry::instance();
        $this->assertNull($registry->get('non_existent'));
    }

    public function testHasStrategy(): void
    {
        $strategy = new class implements ValidationStrategyInterface {
            public function getName(): string { return 'exists'; }
            public function execute(mixed $value, array $args): bool { return true; }
        };

        $registry = GlobalStrategyRegistry::instance();
        
        $this->assertFalse($registry->has('exists'));
        
        $registry->register($strategy);
        
        $this->assertTrue($registry->has('exists'));
    }

    public function testClearStrategies(): void
    {
        $strategy = new class implements ValidationStrategyInterface {
            public function getName(): string { return 'test'; }
            public function execute(mixed $value, array $args): bool { return true; }
        };

        $registry = GlobalStrategyRegistry::instance();
        $registry->register($strategy);
        
        $this->assertEquals(1, $registry->count());

        $result = $registry->clear();
        
        $this->assertSame($registry, $result, 'clear() should return self for chaining');
        $this->assertEquals(0, $registry->count());
        $this->assertFalse($registry->has('test'));
    }

    public function testGetAllStrategies(): void
    {
        $strategy1 = new class implements ValidationStrategyInterface {
            public function getName(): string { return 'test1'; }
            public function execute(mixed $value, array $args): bool { return true; }
        };

        $strategy2 = new class implements ValidationStrategyInterface {
            public function getName(): string { return 'test2'; }
            public function execute(mixed $value, array $args): bool { return true; }
        };

        $registry = GlobalStrategyRegistry::instance();
        $registry->register($strategy1);
        $registry->register($strategy2);

        $all = $registry->getAll();
        
        $this->assertCount(2, $all);
        $this->assertArrayHasKey('test1', $all);
        $this->assertArrayHasKey('test2', $all);
    }

    public function testGetAllMetadata(): void
    {
        $strategy = new class implements ValidationStrategyInterface {
            public function getName(): string { return 'test'; }
            public function execute(mixed $value, array $args): bool { return true; }
        };

        $registry = GlobalStrategyRegistry::instance();
        $registry->register($strategy);

        $metadata = $registry->getAllMetadata();
        
        $this->assertCount(1, $metadata);
        $this->assertArrayHasKey('test', $metadata);
        $this->assertInstanceOf(\Gravity\Registry\ValidationMetadata::class, $metadata['test']);
    }

    public function testFluentInterface(): void
    {
        $strategy1 = new class implements ValidationStrategyInterface {
            public function getName(): string { return 'fluent1'; }
            public function execute(mixed $value, array $args): bool { return true; }
        };

        $strategy2 = new class implements ValidationStrategyInterface {
            public function getName(): string { return 'fluent2'; }
            public function execute(mixed $value, array $args): bool { return true; }
        };

        $registry = GlobalStrategyRegistry::instance()
            ->register($strategy1)
            ->register($strategy2)
            ->clear()
            ->registerMultiple([$strategy1, $strategy2]);

        $this->assertEquals(2, $registry->count());
    }

    public function testResetClearsInstance(): void
    {
        $strategy = new class implements ValidationStrategyInterface {
            public function getName(): string { return 'test'; }
            public function execute(mixed $value, array $args): bool { return true; }
        };

        $registry1 = GlobalStrategyRegistry::instance();
        $registry1->register($strategy);
        
        $this->assertEquals(1, $registry1->count());

        GlobalStrategyRegistry::reset();

        $registry2 = GlobalStrategyRegistry::instance();
        
        $this->assertEquals(0, $registry2->count(), 'Reset should clear all strategies');
        $this->assertNotSame($registry1, $registry2, 'Reset should create new instance');
    }
}