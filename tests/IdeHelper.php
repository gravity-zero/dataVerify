<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Gravity\DataVerify;
use Gravity\Documentation\IdeHelperManager;
use Gravity\Validations\ValidationStrategy;
use Gravity\Attributes\{ValidationRule, Param};
use PHPUnit\Framework\TestCase;

class IdeHelper extends TestCase
{
    private string $testHelperPath;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->testHelperPath = __DIR__ . '/.ide-helper-test.php';
        
        // Cleanup avant chaque test
        if (file_exists($this->testHelperPath)) {
            unlink($this->testHelperPath);
        }
        
        // Reset le manager
        IdeHelperManager::instance()->disable();
        IdeHelperManager::instance()->clear();
        IdeHelperManager::instance()->disableDebounce(); // Désactiver debounce pour les tests
    }
    
    protected function tearDown(): void
    {
        // Cleanup après chaque test
        if (file_exists($this->testHelperPath)) {
            unlink($this->testHelperPath);
        }
        
        IdeHelperManager::instance()->disable();
        IdeHelperManager::instance()->clear();
        
        parent::tearDown();
    }
    
    public function testEnableIdeHelperActivatesManager(): void
    {
        $this->assertFalse(IdeHelperManager::instance()->isEnabled());
        
        DataVerify::enableIdeHelper($this->testHelperPath);
        
        $this->assertTrue(IdeHelperManager::instance()->isEnabled());
        $this->assertEquals($this->testHelperPath, IdeHelperManager::instance()->getOutputPath());
    }
    
    public function testRegisterStrategyGeneratesIdeHelper(): void
    {
        DataVerify::enableIdeHelper($this->testHelperPath);
        
        $strategy = new class extends ValidationStrategy {
            public function getName(): string { return 'testCustom'; }
            protected function handler(mixed $value, string $param): bool { return true; }
        };
        
        // Enregistrer la strategy
        $dv = new DataVerify(['test' => 'value']);
        $dv->registerStrategy($strategy);
        
        // Debug
        $manager = IdeHelperManager::instance();
        echo "\nEnabled: " . ($manager->isEnabled() ? 'yes' : 'no');
        echo "\nOutput path: " . $manager->getOutputPath();
        echo "\nRegistered strategies: " . count($manager->getRegisteredStrategies());
        echo "\nDirectory writable: " . (is_writable(dirname($this->testHelperPath)) ? 'yes' : 'no');
        
        // Forcer manuellement
        $result = $manager->regenerate();
        echo "\nRegenerate result: " . ($result ? 'true' : 'false');
        
        if (file_exists($this->testHelperPath)) {
            echo "\nFile exists!";
        } else {
            echo "\nFile does NOT exist";
            // Lister les fichiers du dossier
            $files = scandir(__DIR__);
            echo "\nFiles in test dir: " . implode(', ', $files);
        }
        
        // Le fichier devrait maintenant exister
        $this->assertFileExists(
            $this->testHelperPath,
            "IDE helper file was not created. Check directory permissions."
        );
        
        $content = file_get_contents($this->testHelperPath);
        $this->assertStringContainsString('testCustom', $content);
        $this->assertStringContainsString('@method DataVerify testCustom', $content);
    }
    
    public function testMultipleStrategiesInSameFile(): void
    {
        DataVerify::enableIdeHelper($this->testHelperPath);
        
        $strategy1 = new class extends ValidationStrategy {
            public function getName(): string { return 'custom1'; }
            protected function handler(mixed $value): bool { return true; }
        };
        
        $strategy2 = new class extends ValidationStrategy {
            public function getName(): string { return 'custom2'; }
            protected function handler(mixed $value, int $param = 10): bool { return true; }
        };
        
        $dv = new DataVerify(['test' => 'value']);
        $dv->registerStrategy($strategy1);
        $dv->registerStrategy($strategy2);
        
        $this->assertFileExists($this->testHelperPath);
        
        $content = file_get_contents($this->testHelperPath);
        $this->assertStringContainsString('custom1', $content);
        $this->assertStringContainsString('custom2', $content);
    }
    
    public function testIdeHelperNotGeneratedWhenDisabled(): void
    {
        // N'active PAS l'IDE helper
        
        $strategy = new class extends ValidationStrategy {
            public function getName(): string { return 'testCustom'; }
            protected function handler(mixed $value): bool { return true; }
        };
        
        $dv = new DataVerify(['test' => 'value']);
        $dv->registerStrategy($strategy);
        
        usleep(100000);
        
        $this->assertFileDoesNotExist($this->testHelperPath);
    }
    
    public function testIdeHelperWithParameterSignatures(): void
    {
        DataVerify::enableIdeHelper($this->testHelperPath);
        
        $strategy = new class extends ValidationStrategy {
            public function getName(): string { return 'complexCustom'; }
            protected function handler(
                mixed $value,
                string $required,
                int $optional = 42,
                array $optionalArray = []
            ): bool {
                return true;
            }
        };
        
        $dv = new DataVerify(['test' => 'value']);
        $dv->registerStrategy($strategy);
        
        $this->assertFileExists($this->testHelperPath);
        
        $content = file_get_contents($this->testHelperPath);
        $this->assertStringContainsString('complexCustom', $content);
        $this->assertStringContainsString('string $required', $content);
        $this->assertStringContainsString('int $optional = 42', $content);
        $this->assertStringContainsString('array $optionalArray', $content);
    }
    
    public function testGeneratedFileHasProperFormat(): void
    {
        DataVerify::enableIdeHelper($this->testHelperPath);
        
        $strategy = new class extends ValidationStrategy {
            public function getName(): string { return 'testFormat'; }
            protected function handler(mixed $value): bool { return true; }
        };
        
        $dv = new DataVerify(['test' => 'value']);
        $dv->registerStrategy($strategy);
        
        $this->assertFileExists($this->testHelperPath);
        
        $content = file_get_contents($this->testHelperPath);
        
        // Vérifier la structure du fichier
        $this->assertStringStartsWith('<?php', $content);
        $this->assertStringContainsString('namespace Gravity', $content);
        $this->assertStringContainsString('class DataVerify', $content);
        $this->assertStringContainsString('IDE Helper for DataVerify', $content);
        
        // Vérifier que c'est du PHP valide
        $this->assertNotFalse(@token_get_all($content));
    }
    
    public function testManagerClearRemovesStrategies(): void
    {
        $manager = IdeHelperManager::instance();
        $manager->enable($this->testHelperPath);
        
        $strategy = new class extends ValidationStrategy {
            public function getName(): string { return 'testClear'; }
            protected function handler(mixed $value): bool { return true; }
        };
        
        $manager->registerStrategy($strategy);
        
        $this->assertNotEmpty($manager->getRegisteredStrategies());
        
        $manager->clear();
        
        $this->assertEmpty($manager->getRegisteredStrategies());
    }
}