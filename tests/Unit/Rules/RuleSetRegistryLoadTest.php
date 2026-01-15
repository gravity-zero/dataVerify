<?php

use Gravity\DataVerify;
use Gravity\Registry\RuleSetRegistry;
use PHPUnit\Framework\TestCase;

class RuleSetRegistryLoadTest extends TestCase
{
    private string $fixturesPath;
    private string $validRulesPath;

    protected function setUp(): void
    {
        RuleSetRegistry::reset();
        $this->fixturesPath = __DIR__ . '/../../fixtures/rules';
        
        // Create a temp directory with only valid rules for some tests
        $this->validRulesPath = sys_get_temp_dir() . '/dataverify-test-rules-' . uniqid();
        mkdir($this->validRulesPath);
        
        // Copy only valid rule files
        copy($this->fixturesPath . '/strongPassword.php', $this->validRulesPath . '/strongPassword.php');
        copy($this->fixturesPath . '/emailFormat.php', $this->validRulesPath . '/emailFormat.php');
    }

    protected function tearDown(): void
    {
        RuleSetRegistry::reset();
        
        // Cleanup temp directory
        if (is_dir($this->validRulesPath)) {
            array_map('unlink', glob($this->validRulesPath . '/*.php'));
            rmdir($this->validRulesPath);
        }
    }

    public function testLoadFromDirectory(): void
    {
        $loaded = RuleSetRegistry::instance()->loadFromDirectory($this->validRulesPath);

        $this->assertCount(2, $loaded);
        $this->assertContains('strongPassword', $loaded);
        $this->assertContains('emailFormat', $loaded);

        // Verify rules are actually registered
        $this->assertTrue(RuleSetRegistry::instance()->has('strongPassword'));
        $this->assertTrue(RuleSetRegistry::instance()->has('emailFormat'));
    }

    public function testLoadFromDirectoryWithDataVerifyHelper(): void
    {
        $loaded = DataVerify::loadRulesFrom($this->validRulesPath);

        $this->assertCount(2, $loaded);
        $this->assertTrue(RuleSetRegistry::instance()->has('strongPassword'));
        $this->assertTrue(RuleSetRegistry::instance()->has('emailFormat'));
    }

    public function testLoadedRulesAreUsable(): void
    {
        DataVerify::loadRulesFrom($this->validRulesPath);

        // Test strongPassword rule
        $data = ['password' => 'WeakPass1'];
        $dv = new DataVerify($data);
        $dv->field('password')->rule('strongPassword');
        
        $this->assertFalse($dv->verify()); // Too short (< 12 chars)

        // Test with valid password
        RuleSetRegistry::reset();
        DataVerify::loadRulesFrom($this->validRulesPath);
        
        $data2 = ['password' => 'StrongPass123!'];
        $dv2 = new DataVerify($data2);
        $dv2->field('password')->rule('strongPassword');
        
        $this->assertTrue($dv2->verify());
    }

    public function testLoadSingleFile(): void
    {
        $ruleName = RuleSetRegistry::instance()->loadFile($this->validRulesPath . '/strongPassword.php');

        $this->assertEquals('strongPassword', $ruleName);
        $this->assertTrue(RuleSetRegistry::instance()->has('strongPassword'));
    }

    public function testLoadFromNonExistentDirectory(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Directory not found');

        RuleSetRegistry::instance()->loadFromDirectory('/nonexistent/directory');
    }

    public function testLoadNonExistentFile(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File not found');

        RuleSetRegistry::instance()->loadFile('/nonexistent/file.php');
    }

    public function testLoadFileNotReturningCallable(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('must return a callable');

        RuleSetRegistry::instance()->loadFile($this->fixturesPath . '/invalidRule.php');
    }

    public function testLoadFromEmptyDirectory(): void
    {
        $emptyDir = sys_get_temp_dir() . '/dataverify-empty-' . uniqid();
        mkdir($emptyDir);

        try {
            $loaded = RuleSetRegistry::instance()->loadFromDirectory($emptyDir);
            $this->assertEmpty($loaded);
        } finally {
            rmdir($emptyDir);
        }
    }

    public function testRulesContainExpectedValidations(): void
    {
        DataVerify::loadRulesFrom($this->validRulesPath);

        $strongPassword = RuleSetRegistry::instance()->get('strongPassword');
        $validations = $strongPassword->getValidations();

        $validationNames = array_column($validations, 'name');
        
        $this->assertContains('minLength', $validationNames);
        $this->assertContains('containsUpper', $validationNames);
        $this->assertContains('containsLower', $validationNames);
        $this->assertContains('containsNumber', $validationNames);
    }
}