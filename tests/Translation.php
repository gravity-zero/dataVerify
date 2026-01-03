<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Gravity\Translation\Translator;
use Gravity\Translation\LoaderFactory;
use Gravity\Translation\TranslationManager;
use Gravity\Translation\Loader\{ArrayLoaderStrategy, PhpLoaderStrategy, YamlLoaderStrategy};
use Gravity\Interfaces\{LoaderStrategyInterface, TranslatorInterface};
use PHPUnit\Framework\TestCase;

class Translation extends TestCase
{
    private string $fixturesDir;

    protected function setUp(): void
    {
        $this->fixturesDir = sys_get_temp_dir() . '/dataverify_translation_tests';
        if (!is_dir($this->fixturesDir)) {
            mkdir($this->fixturesDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        if (is_dir($this->fixturesDir)) {
            $files = glob($this->fixturesDir . '/*');
            if ($files !== false) {
                array_map('unlink', $files);
            }
            rmdir($this->fixturesDir);
        }
    }

    // ===== Basic Translator Tests =====

    public function testTranslatorDefaultLocale(): void
    {
        $translator = new Translator('fr');
        $this->assertEquals('fr', $translator->getLocale());
    }

    public function testTranslatorSetLocale(): void
    {
        $translator = new Translator('en');
        $translator->setLocale('fr');
        $this->assertEquals('fr', $translator->getLocale());
    }

    public function testTransWithArrayResource(): void
    {
        $translator = new Translator('en');
        $translator->addResource([
            'validation.required' => 'The field {field} is required',
            'validation.email' => 'Invalid email'
        ], 'en', 'validators');

        $result = $translator->trans('validation.required', ['field' => 'email'], 'validators');
        $this->assertEquals('The field email is required', $result);
    }

    public function testTransWithPlaceholders(): void
    {
        $translator = new Translator('en');
        $translator->addResource([
            'validation.between' => 'The field {field} must be between {min} and {max}'
        ], 'en', 'validators');

        $result = $translator->trans(
            'validation.between',
            ['field' => 'age', 'min' => '18', 'max' => '100'],
            'validators'
        );
        $this->assertEquals('The field age must be between 18 and 100', $result);
    }

    public function testTransWithBracketPlaceholders(): void
    {
        $translator = new Translator('en');
        $translator->addResource([
            'greeting' => 'Hello {name}!'
        ], 'en', 'messages');

        $result1 = $translator->trans('greeting', ['{name}' => 'Alice'], 'messages');
        $result2 = $translator->trans('greeting', ['name' => 'Bob'], 'messages');

        $this->assertEquals('Hello Alice!', $result1);
        $this->assertEquals('Hello Bob!', $result2);
    }

    public function testTransMissingKeyReturnsKey(): void
    {
        $translator = new Translator('en');
        $translator->addResource(['key' => 'value'], 'en', 'messages');

        $result = $translator->trans('missing.key', [], 'messages');
        $this->assertEquals('missing.key', $result);
    }

    public function testTransFallbackLocale(): void
    {
        $translator = new Translator('fr');
        $translator->setFallbackLocale('en');
        
        $translator->addResource([
            'validation.required' => 'Required field'
        ], 'en', 'validators');

        $result = $translator->trans('validation.required', [], 'validators');
        $this->assertEquals('Required field', $result);
    }

    public function testTransMultipleDomains(): void
    {
        $translator = new Translator('en');
        
        $translator->addResource([
            'welcome' => 'Welcome!'
        ], 'en', 'messages');
        
        $translator->addResource([
            'validation.required' => 'Required'
        ], 'en', 'validators');

        $msg = $translator->trans('welcome', [], 'messages');
        $val = $translator->trans('validation.required', [], 'validators');

        $this->assertEquals('Welcome!', $msg);
        $this->assertEquals('Required', $val);
    }

    // ===== ArrayLoaderStrategy Tests =====

    public function testArrayLoaderSupportsArray(): void
    {
        $loader = new ArrayLoaderStrategy();
        $this->assertTrue($loader->supports(['key' => 'value']));
        $this->assertFalse($loader->supports('string'));
        $this->assertFalse($loader->supports(123));
    }

    public function testArrayLoaderLoad(): void
    {
        $loader = new ArrayLoaderStrategy();
        $data = ['key1' => 'value1', 'key2' => 'value2'];
        $result = $loader->load($data, 'en', 'messages');

        $this->assertEquals($data, $result);
    }

    public function testArrayLoaderThrowsOnInvalidResource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $loader = new ArrayLoaderStrategy();
        $loader->load('not-an-array', 'en', 'messages');
    }

    // ===== PhpLoaderStrategy Tests =====

    public function testPhpLoaderSupportsPhpFiles(): void
    {
        $loader = new PhpLoaderStrategy();
        $this->assertTrue($loader->supports('/path/to/file.php'));
        $this->assertFalse($loader->supports('/path/to/file.yaml'));
        $this->assertFalse($loader->supports(['array']));
    }

    public function testPhpLoaderLoad(): void
    {
        $file = $this->fixturesDir . '/test.php';
        file_put_contents($file, '<?php return ["key" => "value"];');

        $loader = new PhpLoaderStrategy();
        $result = $loader->load($file, 'en', 'messages');

        $this->assertEquals(['key' => 'value'], $result);
    }

    public function testPhpLoaderThrowsOnMissingFile(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Translation file not found');

        $loader = new PhpLoaderStrategy();
        $loader->load('/non/existent/file.php', 'en', 'messages');
    }

    public function testPhpLoaderThrowsOnNonArrayReturn(): void
    {
        $file = $this->fixturesDir . '/invalid.php';
        file_put_contents($file, '<?php return "not an array";');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must return an array');

        $loader = new PhpLoaderStrategy();
        $loader->load($file, 'en', 'messages');
    }

    // ===== YamlLoaderStrategy Tests =====

    public function testYamlLoaderSupportsYamlFiles(): void
    {
        $loader = new YamlLoaderStrategy();
        $this->assertTrue($loader->supports('/path/to/file.yaml'));
        $this->assertTrue($loader->supports('/path/to/file.yml'));
        $this->assertFalse($loader->supports('/path/to/file.php'));
        $this->assertFalse($loader->supports(['array']));
    }

    public function testYamlLoaderLoad(): void
    {
        $file = $this->fixturesDir . '/test.yaml';
        file_put_contents($file, "key1: value1\nkey2: value2");

        $loader = new YamlLoaderStrategy();
        $result = $loader->load($file, 'en', 'messages');

        $this->assertEquals(['key1' => 'value1', 'key2' => 'value2'], $result);
    }

    public function testYamlLoaderWithQuotedStrings(): void
    {
        $file = $this->fixturesDir . '/quoted.yaml';
        file_put_contents($file, 'message: "Hello {name}"');

        $loader = new YamlLoaderStrategy();
        $result = $loader->load($file, 'en', 'messages');

        $this->assertEquals(['message' => 'Hello {name}'], $result);
    }

    public function testYamlLoaderThrowsOnMissingFile(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Translation file not found');

        $loader = new YamlLoaderStrategy();
        $loader->load('/non/existent/file.yaml', 'en', 'messages');
    }

    // ===== LoaderFactory Tests =====

    public function testFactoryGetLoaderForArray(): void
    {
        $factory = LoaderFactory::createDefault();
        $loader = $factory->getLoader(['key' => 'value']);

        $this->assertInstanceOf(ArrayLoaderStrategy::class, $loader);
    }

    public function testFactoryGetLoaderForPhpFile(): void
    {
        $factory = LoaderFactory::createDefault();
        $loader = $factory->getLoader('/path/to/file.php');

        $this->assertInstanceOf(PhpLoaderStrategy::class, $loader);
    }

    public function testFactoryGetLoaderForYamlFile(): void
    {
        $factory = LoaderFactory::createDefault();
        $loader = $factory->getLoader('/path/to/file.yaml');

        $this->assertInstanceOf(YamlLoaderStrategy::class, $loader);
    }

    public function testFactoryThrowsOnUnsupportedResource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No loader found');

        $factory = LoaderFactory::createDefault();
        $factory->getLoader(12345);
    }

    public function testFactoryRegisterCustomStrategy(): void
    {
        $customStrategy = new class implements LoaderStrategyInterface {
            public function supports(mixed $resource): bool {
                return is_int($resource);
            }
            public function load(mixed $resource, string $locale, string $domain = 'messages'): array {
                return ['custom' => 'loaded'];
            }
        };

        $factory = new LoaderFactory();
        $factory->registerStrategy($customStrategy);

        $loader = $factory->getLoader(123);
        $result = $loader->load(123, 'en', 'messages');

        $this->assertEquals(['custom' => 'loaded'], $result);
    }

    public function testFactoryAllowsRegisteringCustomStrategy(): void
    {
        $factory = new LoaderFactory();
        
        $customStrategy = new class implements LoaderStrategyInterface {
            public function supports(mixed $resource): bool {
                return is_int($resource);
            }
            public function load(mixed $resource, string $locale, string $domain = 'messages'): array {
                return ['test' => 'value'];
            }
        };
        
        // Test que registerStrategy est bien public et utilisable
        $factory->registerStrategy($customStrategy);
        
        $loader = $factory->getLoader(123);
        $this->assertInstanceOf(get_class($customStrategy), $loader);
    }

    public function testCreateDefaultRegistersAllLoaders(): void
    {
        $factory = LoaderFactory::createDefault();
        
        // Doit supporter les 3 formats
        $this->assertInstanceOf(ArrayLoaderStrategy::class, $factory->getLoader(['key' => 'val']));
        $this->assertInstanceOf(PhpLoaderStrategy::class, $factory->getLoader('file.php'));
        $this->assertInstanceOf(YamlLoaderStrategy::class, $factory->getLoader('file.yaml'));
    }

    public function testCreateDefaultDoesNotAcceptUnsupportedFormats(): void
    {
        $factory = LoaderFactory::createDefault();
        
        $this->expectException(\InvalidArgumentException::class);
        $factory->getLoader('unsupported.txt');
    }

    public function testTranslationManagerLoadsEnglishByDefault(): void
    {
        $manager = new TranslationManager();
        
        // Doit avoir chargé en.php automatiquement
        $message = $manager->getValidationMessage('required', 'email', '');
        
        $this->assertStringContainsString('required', $message);
        $this->assertStringContainsString('email', $message);
    }

    public function testNewTranslationManagerHasTranslator(): void
    {
        $manager = new TranslationManager();
        
        $translator = $manager->getTranslator();
        
        $this->assertNotNull($translator);
        $this->assertInstanceOf(TranslatorInterface::class, $translator);
    }

    public function testFallbackLocaleWorks(): void
    {
        // Créer un translator vide
        $translator = new Translator('fr');
        $translator->setFallbackLocale('en');
        
        // Charger SEULEMENT l'anglais (fallback)
        $enFile = __DIR__ . '/../src/Locales/en.php';
        $translator->addResource($enFile, 'en', 'validators');
        
        // Charger un FR partiel qui ne contient PAS validation.required
        $tmpFile = sys_get_temp_dir() . '/fr_partial.php';
        file_put_contents($tmpFile, '<?php return ["validation.custom" => "Message FR"];');
        $translator->addResource($tmpFile, 'fr', 'validators');
        
        // Demander une clé qui existe en EN mais PAS dans notre FR partiel
        $message = $translator->trans('validation.required', ['field' => 'email'], 'validators', 'fr');
        
        // Doit fallback vers EN
        $this->assertStringContainsString('required', $message);
        $this->assertStringNotContainsString('requis', $message);
        
        @unlink($tmpFile);
    }

    public function testTranslationManagerHasTranslatorAfterConstruction(): void
    {
        $manager = new TranslationManager();
        
        $this->assertNotNull($manager->getTranslator(), 
            'TranslationManager must initialize translator in constructor');
    }

    public function testLoaderFactoryCreatesWithAllStrategies(): void
    {
        $factory = LoaderFactory::createDefault();
        
        $this->assertInstanceOf(ArrayLoaderStrategy::class, $factory->getLoader([]));
        $this->assertInstanceOf(PhpLoaderStrategy::class, $factory->getLoader('test.php'));
        $this->assertInstanceOf(YamlLoaderStrategy::class, $factory->getLoader('test.yaml'));
    }

    public function testDefaultTranslatorCanTranslateRequiredValidation(): void
    {
        $manager = new TranslationManager();
        $message = $manager->getValidationMessage('_required', 'email', '');
        
        // Si setFallbackLocale() ou addResource() sont supprimés, 
        // on aura le message par défaut au lieu de la traduction
        $this->assertStringContainsString('required', strtolower($message));
        $this->assertNotEquals(
            "The field 'email' failed the test '_required'", 
            $message,
            'Should use translated message, not default fallback'
        );
    }

    public function testLoaderFactorySupportsDifferentResourceTypes(): void
    {
        $factory = LoaderFactory::createDefault();
        
        try {
            $arrayLoader = $factory->getLoader([]);
            $this->assertInstanceOf(ArrayLoaderStrategy::class, $arrayLoader);
        } catch (\Exception $e) {
            $this->fail('ArrayLoaderStrategy should be registered');
        }
        
        try {
            $phpLoader = $factory->getLoader('test.php');
            $this->assertInstanceOf(PhpLoaderStrategy::class, $phpLoader);
        } catch (\Exception $e) {
            $this->fail('PhpLoaderStrategy should be registered');
        }
        
        try {
            $yamlLoader = $factory->getLoader('test.yaml');
            $this->assertInstanceOf(YamlLoaderStrategy::class, $yamlLoader);
        } catch (\Exception $e) {
            $this->fail('YamlLoaderStrategy should be registered');
        }
    }

    // ===== Integration Tests =====

    public function testIntegrationWithPhpFile(): void
    {
        $file = $this->fixturesDir . '/en.php';
        file_put_contents($file, '<?php return [
            "validation.required" => "The field {field} is required",
            "validation.email" => "Invalid email address"
        ];');

        $translator = new Translator('en');
        $translator->addResource($file, 'en', 'validators');

        $result = $translator->trans('validation.required', ['field' => 'username'], 'validators');
        $this->assertEquals('The field username is required', $result);
    }

    public function testIntegrationWithYamlFile(): void
    {
        $file = $this->fixturesDir . '/fr.yaml';
        file_put_contents($file, 
            "validation.required: \"Le champ {field} est requis\"\n" .
            "validation.email: \"Adresse email invalide\""
        );

        $translator = new Translator('fr');
        $translator->addResource($file, 'fr', 'validators');

        $result = $translator->trans('validation.required', ['field' => 'email'], 'validators');
        $this->assertEquals('Le champ email est requis', $result);
    }

    public function testIntegrationMultipleResources(): void
    {
        $translator = new Translator('en');

        $translator->addResource([
            'validation.required' => 'Required'
        ], 'en', 'validators');

        $phpFile = $this->fixturesDir . '/extra.php';
        file_put_contents($phpFile, '<?php return ["validation.email" => "Invalid email"];');
        $translator->addResource($phpFile, 'en', 'validators');

        $yamlFile = $this->fixturesDir . '/more.yaml';
        file_put_contents($yamlFile, 'validation.string: "Must be a string"');
        $translator->addResource($yamlFile, 'en', 'validators');

        $this->assertEquals('Required', $translator->trans('validation.required', [], 'validators'));
        $this->assertEquals('Invalid email', $translator->trans('validation.email', [], 'validators'));
        $this->assertEquals('Must be a string', $translator->trans('validation.string', [], 'validators'));
    }

    public function testIntegrationLocaleOverride(): void
    {
        $translator = new Translator('en');
        
        $translator->addResource(['greeting' => 'Hello'], 'en', 'messages');
        $translator->addResource(['greeting' => 'Bonjour'], 'fr', 'messages');

        $this->assertEquals('Hello', $translator->trans('greeting', [], 'messages'));
        $this->assertEquals('Bonjour', $translator->trans('greeting', [], 'messages', 'fr'));
    }

    public function testPhpLoaderRejectsNonPhpFiles(): void
    {
        $loader = new PhpLoaderStrategy();
        
        $this->assertFalse($loader->supports('/path/to/file.txt'));
        $this->assertFalse($loader->supports('/path/to/file.yaml'));
        $this->assertFalse($loader->supports('/path/to/file'));
        $this->assertTrue($loader->supports('/path/to/file.php'));
    }

    public function testPhpLoaderRejectsNonString(): void
    {
        $loader = new PhpLoaderStrategy();
        
        $this->assertFalse($loader->supports(123));
        $this->assertFalse($loader->supports([]));
        $this->assertFalse($loader->supports(null));
    }

    public function testPhpLoaderReturnsAllTranslations(): void
    {
        $file = $this->fixturesDir . '/multiple.php';
        file_put_contents($file, '<?php return [
            "key1" => "value1",
            "key2" => "value2",
            "key3" => "value3"
        ];');
        
        $loader = new PhpLoaderStrategy();
        $result = $loader->load($file, 'en', 'messages');
        
        $this->assertCount(3, $result);
        $this->assertEquals('value1', $result['key1']);
        $this->assertEquals('value2', $result['key2']);
        $this->assertEquals('value3', $result['key3']);
        
        @unlink($file);
    }

    public function testTranslationManagerInitializesWithEnglishByDefault(): void
    {
        $manager = new TranslationManager();
        $translator = $manager->getTranslator();
        
        $this->assertNotNull($translator);
        
        $message = $manager->getValidationMessage('_required', 'email', '');
        
        $this->assertNotEquals("The field 'email' failed the test '_required'", $message);

        $this->assertStringContainsString('required', strtolower($message));
    }

    public function testDefaultEnglishLocaleFileExists(): void
    {
        $manager = new TranslationManager();
        $message = $manager->getValidationMessage('_required', 'test', '');
        
        $this->assertNotEquals("The field 'test' failed the test '_required'", $message);
    }

    public function testTranslatorMergesMultipleResources(): void
    {
        $translator = new Translator('en');
        
        // Ajouter 2 ressources avec des clés différentes
        $translator->addResource(['key1' => 'value1'], 'en', 'test');
        $translator->addResource(['key2' => 'value2'], 'en', 'test');
        
        // Si array_merge est unwrap, seulement une des deux sera présente
        $this->assertEquals('value1', $translator->trans('key1', [], 'test'));
        $this->assertEquals('value2', $translator->trans('key2', [], 'test'));
    }

    public function testPhpLoaderReturnsAllMessages(): void
    {
        $loader = new PhpLoaderStrategy();
        $enFile = __DIR__ . '/../src/Locales/en.php';
        
        $messages = $loader->load($enFile, 'en', 'validators');
        
        // Si ArrayOneItem est actif, seulement 1 message est retourné
        $this->assertGreaterThan(1, count($messages), 
            'Should load all messages from file, not just one');
        
        // Vérifie quelques clés connues
        $this->assertArrayHasKey('validation.required', $messages);
        $this->assertArrayHasKey('validation.email', $messages);
    }

    public function testTranslatorInitializesLocaleStructure(): void
    {
        $translator = new Translator('fr');
        
        // Première ressource pour 'fr'
        $translator->addResource(['test' => 'valeur'], 'fr', 'messages');
        
        // Si isset() est inversé, ça pourrait écraser
        $this->assertEquals('valeur', $translator->trans('test', [], 'messages', 'fr'));
        
        // Deuxième ressource pour 'fr' - doit être mergée
        $translator->addResource(['test2' => 'valeur2'], 'fr', 'messages');
        
        // Les deux doivent exister
        $this->assertEquals('valeur', $translator->trans('test', [], 'messages', 'fr'));
        $this->assertEquals('valeur2', $translator->trans('test2', [], 'messages', 'fr'));
    }
    
}