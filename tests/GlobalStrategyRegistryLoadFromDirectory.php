<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Gravity\Registry\GlobalStrategyRegistry;
use PHPUnit\Framework\TestCase;

class GlobalStrategyRegistryLoadFromDirectory extends TestCase
{
    protected function setUp(): void
    {
        GlobalStrategyRegistry::reset();
    }

    protected function tearDown(): void
    {
        GlobalStrategyRegistry::reset();
    }

    public function testLoadFromValidDirectory(): void
    {
        $registry = GlobalStrategyRegistry::instance();
        
        $result = $registry->loadFromDirectory(
            path: __DIR__ . '/fixtures/strategies',
            namespace: 'Tests\\Fixtures\\Strategies'
        );

        $this->assertSame($registry, $result, 'loadFromDirectory() should return self for chaining');
        
        // Should load ValidStrategy and ValidStrategyWithAnnotation
        // Should skip NotAStrategy (not implementing interface) and AbstractStrategy (abstract)
        $this->assertGreaterThanOrEqual(2, $registry->count());
        $this->assertTrue($registry->has('valid_fixture'));
        $this->assertTrue($registry->has('another_valid'));
    }

    public function testLoadFromDirectoryWithCustomPattern(): void
    {
        $registry = GlobalStrategyRegistry::instance();
        
        $registry->loadFromDirectory(
            path: __DIR__ . '/fixtures/strategies',
            namespace: 'Tests\\Fixtures\\Strategies'
        );

        // Should only load files starting with "Valid"
        $this->assertTrue($registry->has('valid_fixture'));
        $this->assertTrue($registry->has('another_valid'));
    }

    public function testLoadFromNonExistentDirectory(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Directory not found');

        GlobalStrategyRegistry::instance()->loadFromDirectory(
            path: '/this/does/not/exist',
            namespace: 'Some\\Namespace'
        );
    }

    public function testLoadFromDirectorySkipsInvalidFiles(): void
    {
        $registry = GlobalStrategyRegistry::instance();
        
        $registry->loadFromDirectory(
            path: __DIR__ . '/fixtures/strategies',
            namespace: 'Tests\\Fixtures\\Strategies'
        );

        // Should NOT load AbstractStrategy (abstract)
        $this->assertFalse($registry->has('abstract'));
        
        // Should NOT load NotAStrategy (doesn't implement interface)
        $this->assertFalse($registry->has('not_a_strategy'));
    }
}