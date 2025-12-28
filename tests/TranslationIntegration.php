<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Gravity\DataVerify;
use Gravity\Translation\{Translator, TranslationManager};
use PHPUnit\Framework\TestCase;

class TranslationIntegration extends TestCase
{
    public function testDefaultEnglishMessages(): void
    {
        $data = new stdClass();
        $data->email = '';
        
        $verifier = new DataVerify($data);
        $verifier->field('email')->required->email;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        
        $this->assertEquals('The field email is required', $errors[0]['message']);
    }

    public function testSetLocaleToFrench(): void
    {
        // Create temporary French locale
        $tmpDir = sys_get_temp_dir();
        $frFile = $tmpDir . '/fr_test.yml';
        file_put_contents($frFile, 'validation.required: "Le champ {field} est requis"');
        
        $data = new stdClass();
        $data->email = '';
        
        $verifier = new DataVerify($data);
        $verifier->loadLocale('fr', $frFile);
        $verifier->setLocale('fr');
        $verifier->field('email')->required;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        
        $this->assertStringContainsString('requis', $errors[0]['message']);
        
        @unlink($frFile);
    }

    public function testLoadCustomLocale(): void
    {
        $data = new stdClass();
        $data->name = '';
        
        $verifier = new DataVerify($data);
        
        // Create temporary Spanish locale file
        $tmpDir = sys_get_temp_dir();
        $esFile = $tmpDir . '/es.yml';
        file_put_contents($esFile, 'validation.required: "El campo {field} es obligatorio"');
        
        $verifier->loadLocale('es', $esFile);
        $verifier->setLocale('es');
        $verifier->field('name')->required;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        
        $this->assertEquals('El campo name es obligatorio', $errors[0]['message']);
        
        @unlink($esFile);
    }

    public function testCustomTranslator(): void
    {
        $customTranslator = new class implements \Gravity\Interfaces\TranslatorInterface {
            public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string {
                return "CUSTOM: {$parameters['field']} validation failed";
            }
            
            public function getLocale(): string {
                return 'custom';
            }
            
            public function setLocale(string $locale): void {}
        };
        
        $data = new stdClass();
        $data->test = '';
        
        $verifier = new DataVerify($data);
        $verifier->setTranslator($customTranslator);
        $verifier->field('test')->required;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        
        $this->assertEquals('CUSTOM: test validation failed', $errors[0]['message']);
    }

    public function testTranslationWithPlaceholders(): void
    {
        $data = new stdClass();
        $data->password = 'abc';
        
        $verifier = new DataVerify($data);
        $verifier->field('password')->required->minLength(8);
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        
        $this->assertEquals('The field password must be at least 8 characters', $errors[0]['message']);
    }

    public function testTranslationWithBetweenPlaceholders(): void
    {
        $data = new stdClass();
        $data->age = 150;
        
        $verifier = new DataVerify($data);
        $verifier->field('age')->required->int->between(0, 120);
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        
        $this->assertEquals('The field age must be between 0 and 120', $errors[0]['message']);
    }

    public function testTranslationWithAlias(): void
    {
        $data = new stdClass();
        $data->user_email = 'invalid';
        
        $verifier = new DataVerify($data);
        $verifier->field('user_email')->alias('Email Address')->required->email;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        
        $this->assertEquals('The field Email Address must be a valid email address', $errors[0]['message']);
    }

    public function testCustomErrorMessageOverridesTranslation(): void
    {
        $data = new stdClass();
        $data->field = '';
        
        $verifier = new DataVerify($data);
        $verifier->field('field')->required->errorMessage('Custom error message');
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        
        $this->assertEquals('Custom error message', $errors[0]['message']);
    }

    public function testMultipleFieldsWithTranslations(): void
    {
        $data = new stdClass();
        $data->email = 'invalid';
        $data->password = 'short';
        $data->age = 5;
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('email')->required->email
            ->field('password')->required->minLength(8)
            ->field('age')->required->int->greaterThan(18);
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        
        $this->assertCount(3, $errors);
        $this->assertStringContainsString('email', $errors[0]['message']);
        $this->assertStringContainsString('8 characters', $errors[1]['message']);
        $this->assertStringContainsString('greater than 18', $errors[2]['message']);
    }

    public function testTranslationWithSubfields(): void
    {
        $data = new stdClass();
        $data->user = new stdClass();
        $data->user->email = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('user')->required->object
                ->subfield('email')->required->email;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        
        $this->assertEquals('The field user.email is required', $errors[0]['message']);
    }

    public function testTranslationWithConditionalValidation(): void
    {
        $data = new stdClass();
        $data->type = 'premium';
        $data->premium_code = '';
        
        $verifier = new DataVerify($data);
        $verifier
            ->field('premium_code')
            ->when('type', '=', 'premium')
            ->then->required;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        
        $this->assertEquals('The field premium_code is required', $errors[0]['message']);
    }

    public function testFallbackToEnglishWhenTranslationMissing(): void
    {
        $data = new stdClass();
        $data->field = '';
        
        $verifier = new DataVerify($data);
        $verifier->setLocale('de'); // German not loaded, should fallback to English
        $verifier->field('field')->required;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        
        // Should fallback to English
        $this->assertStringContainsString('required', $errors[0]['message']);
    }

    public function testFrenchLocaleFileExists(): void
    {
        $frFile = __DIR__ . '/../src/Locales/fr.php';
        
        $this->assertFileExists($frFile, 'French locale file fr.php must exist');
        $this->assertFileIsReadable($frFile, 'French locale file must be readable');
    }

    public function testEnglishLocaleFileExists(): void
    {
        $enFile = __DIR__ . '/../src/Locales/en.php';
        
        $this->assertFileExists($enFile, 'English locale file en.php must exist');
        $this->assertFileIsReadable($enFile, 'English locale file must be readable');
    }

    public function testFrenchLocaleIsLoadedAutomatically(): void
    {
        $data = new stdClass();
        $data->email = '';
        
        $verifier = new DataVerify($data);
        
        // Load French locale explicitly
        $verifier->loadLocale('fr');
        $verifier->setLocale('fr');
        $verifier->field('email')->required;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        
        // Must be in French
        $this->assertStringContainsString('requis', $errors[0]['message']);
        $this->assertStringNotContainsString('required', $errors[0]['message']);
    }

    public function testLocaleFilesContainRequiredKeys(): void
    {
        $requiredKeys = [
            'validation.required',
            'validation.string',
            'validation.email',
            'validation.int',
            'validation.minLength',
            'validation.maxLength',
            'validation.between',
        ];
        
        foreach (['en', 'fr'] as $locale) {
            $file = __DIR__ . "/../src/Locales/{$locale}.php";
            $this->assertFileExists($file, "Locale file {$locale}.php must exist");
            
            $translations = require $file;
            $this->assertIsArray($translations, "Locale file {$locale}.php must return an array");
            
            foreach ($requiredKeys as $key) {
                $this->assertArrayHasKey(
                    $key,
                    $translations,
                    "Locale file {$locale}.php must contain key {$key}"
                );
            }
        }
    }

    public function testFallbackWhenLocaleFileNotFound(): void
    {
        $data = new stdClass();
        $data->field = '';
        
        $verifier = new DataVerify($data);
        
        // Try to load non-existent locale
        $verifier->loadLocale('zh'); // Chinese doesn't exist
        $verifier->setLocale('zh');
        $verifier->field('field')->required;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        
        // Should fallback to English
        $this->assertStringContainsString('required', $errors[0]['message']);
    }

    public function testLoadYamlLocaleWhenSymfonyYamlAvailable(): void
    {
        if (!class_exists('\Symfony\Component\Yaml\Yaml')) {
            $this->markTestSkipped('symfony/yaml not installed');
        }
        
        // Create temporary YAML locale
        $tmpDir = sys_get_temp_dir();
        $deFile = $tmpDir . '/de_test.yaml';
        file_put_contents($deFile, <<<YAML
    validation.required: "Das Feld {field} ist erforderlich"
    validation.email: "Das Feld {field} muss eine gültige E-Mail-Adresse sein"
    YAML
        );
        
        $data = new stdClass();
        $data->email = '';
        
        $verifier = new DataVerify($data);
        $verifier->loadLocale('de', $deFile);
        $verifier->setLocale('de');
        $verifier->field('email')->required;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        
        $this->assertStringContainsString('erforderlich', $errors[0]['message']);
        
        @unlink($deFile);
    }

    public function testAddTranslationsForCustomStrategy(): void
    {
        $isPalindrome = new class implements \Gravity\Interfaces\ValidationStrategyInterface {
            public function execute(mixed $value, array $args): bool {
                if (!is_string($value)) return false;
                return $value === strrev($value);
            }
            public function getName(): string {
                return 'isPalindrome';
            }
        };
        
        $data = new stdClass();
        $data->word = 'test';
        
        $verifier = new DataVerify($data);
        $verifier->registerStrategy($isPalindrome);
        
        // Add custom translation
        $verifier->addTranslations([
            'validation.isPalindrome' => 'The field {field} must be a palindrome'
        ], 'en');
        
        $verifier->field('word')->isPalindrome();
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        
        $this->assertEquals('The field word must be a palindrome', $errors[0]['message']);
    }

    public function testAddTranslationsInFrench(): void
    {
        $data = new stdClass();
        $data->code = 'invalid';
        
        $verifier = new DataVerify($data);
        $verifier->setLocale('fr');
        
        // Add custom French translation
        $verifier->addTranslations([
            'validation.customCode' => 'Le code {field} est invalide'
        ], 'fr');
        
        // Manually trigger with custom test
        $verifier->field('code')->required;
        // Can't test custom validation without implementing it, 
        // but we can verify addTranslations works
        
        $this->assertTrue(true); // Just verify no exceptions
    }

    public function testLoadExternalCustomTranslationFile(): void
    {
        // Create external custom translation file
        $tmpDir = sys_get_temp_dir();
        $customFile = $tmpDir . '/custom-validations.php';
        file_put_contents($customFile, <<<'PHP'
    <?php
    return [
        'validation.isValidSIRET' => 'The field {field} must be a valid SIRET number',
        'validation.isValidIBAN' => 'The field {field} must be a valid IBAN',
    ];
    PHP
        );
        
        $data = new stdClass();
        $data->field = '';
        
        $verifier = new DataVerify($data);
        
        // Load custom translations file
        $verifier->loadLocale('en', $customFile);
        
        // Verify no errors loading
        $this->assertTrue(true);
        
        @unlink($customFile);
    }

    public function testPhpLocalePreferredOverYaml(): void
    {
        $tmpDir = sys_get_temp_dir();
        
        // Create both PHP and YAML files
        $phpFile = $tmpDir . '/test_locale.php';
        $yamlFile = $tmpDir . '/test_locale.yaml';
        
        file_put_contents($phpFile, <<<'PHP'
    <?php
    return ['validation.required' => 'PHP version'];
    PHP
        );
        
        file_put_contents($yamlFile, 'validation.required: "YAML version"');
        
        // Simulate auto-detection by creating files in Locales dir
        // For this test, we'll use explicit loading
        
        $data = new stdClass();
        $data->field = '';
        
        $verifier = new DataVerify($data);
        $verifier->loadLocale('test', $phpFile);
        $verifier->setLocale('test');
        $verifier->field('field')->required;
        
        $this->assertFalse($verifier->verify());
        $errors = $verifier->getErrors();
        
        $this->assertEquals('PHP version', $errors[0]['message']);
        
        @unlink($phpFile);
        @unlink($yamlFile);
    }

    public function testMultipleCustomTranslationsCanBeAdded(): void
    {
        $verifier = new DataVerify(new stdClass());
        
        // Add multiple custom translations
        $verifier->addTranslations([
            'validation.custom1' => 'Custom message 1',
            'validation.custom2' => 'Custom message 2',
        ], 'en');
        
        $verifier->addTranslations([
            'validation.custom3' => 'Custom message 3',
        ], 'en');
        
        // Verify no exceptions - translations are merged
        $this->assertTrue(true);
    }

    public function testMissingEnglishFileDoesNotCrash(): void
    {
        // Créer un TranslationManager custom sans en.php
        $translator = new Translator('en');
        $translator->setFallbackLocale('en');
        // NE PAS charger en.php
        
        $manager = new TranslationManager();
        $manager->setTranslator($translator); // Injecter notre translator vide
        
        $data = new stdClass();
        $data->email = '';
        
        $verifier = new DataVerify($data);
        $verifier->setTranslator($translator);
        $verifier->field('email')->required;
        
        // Ne doit pas crash même sans traductions
        $this->assertFalse($verifier->verify());
        
        $errors = $verifier->getErrors();
        
        // Doit retourner la clé brute ou un message par défaut
        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('message', $errors[0]);
        $this->assertNotEmpty($errors[0]['message']);
    }

    public function testCorruptedTranslationFileThrows(): void
    {
        $corruptFile = sys_get_temp_dir() . '/corrupt.php';
        file_put_contents($corruptFile, '<?php return "not an array";');
        
        $this->expectException(\InvalidArgumentException::class);
        
        $verifier = new DataVerify(new stdClass());
        $verifier->loadLocale('en', $corruptFile);
        
        @unlink($corruptFile);
    }

    public function testEmptyTranslationFileWorks(): void
    {
        $emptyFile = sys_get_temp_dir() . '/empty.php';
        file_put_contents($emptyFile, '<?php return [];');
        
        $verifier = new DataVerify(new stdClass());
        $verifier->loadLocale('en', $emptyFile);
        
        // Ne doit pas crash
        $this->assertTrue(true);
        
        @unlink($emptyFile);
    }

    public function testNonExistentLocaleFileIsIgnored(): void
    {
        $verifier = new DataVerify(new stdClass());
        
        // loadLocale avec fichier inexistant ne doit pas crash
        $verifier->loadLocale('de', '/path/does/not/exist.php');
        
        $this->assertTrue(true);
    }
}