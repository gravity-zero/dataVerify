<?php

namespace Gravity\Translation;

use Gravity\Interfaces\TranslatorInterface;

class TranslationManager
{
    private ?TranslatorInterface $translator = null;
    private bool $useDefaultTranslations = true;

    public function __construct()
    {
        $this->initializeDefaultTranslator();
    }

    /**
     * Set a custom translator
     */
    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
        $this->useDefaultTranslations = false;
    }

    /**
     * Get the current translator
     */
    public function getTranslator(): ?TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * Set the locale for translations
     */
    public function setLocale(string $locale): void
    {
        if ($this->translator instanceof Translator) {
            $this->translator->setLocale($locale);
        }
    }

    /**
     * Get translated validation error message
     *
     * @param string $testName Validation test name (e.g., 'required', '_email')
     * @param string $field Field name or alias
     * @param mixed $value Field value
     * @param array<string, mixed> $params Additional parameters (e.g., min, max)
     * @return string
     */
    public function getValidationMessage(
        string $testName,
        string $field,
        mixed $value,
        array $params = []
    ): string {
        if (!$this->translator) {
            return $this->getDefaultMessage($testName, $field);
        }

        $key = 'validation.' . $testName;
        
        $parameters = array_merge([
            'field' => $field,
            'value' => $this->formatValue($value)
        ], $params);

        return $this->translator->trans($key, $parameters, 'validators');
    }

    /**
     * Initialize default translator with English translations
     */
    private function initializeDefaultTranslator(): void
    {
        $translator = new Translator('en');
        $translator->setFallbackLocale('en');

        $enFile = __DIR__ . '/../Locales/en.php';
        
        if (!file_exists($enFile)) {
            throw new \RuntimeException("Default locale file not found: {$enFile}");
        }
        
        $translator->addResource($enFile, 'en', 'validators');

        $this->translator = $translator;
    }

    /**
     * Get default non-translated message
     */
    private function getDefaultMessage(string $testName, string $field): string
    {
        return "The field '{$field}' failed the test '{$testName}'";
    }

    /**
     * Format value for display in error message
     */
    private function formatValue(mixed $value): string
    {
        if (is_null($value)) {
            return 'null';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if ($value instanceof \DateTime) {
            return $value->format('Y-m-d H:i:s');
        }
        if (is_scalar($value)) {
            return (string)$value;
        }
        if (is_array($value)) {
            return 'array';
        }
        if (is_object($value)) {
            return 'object';
        }
        return gettype($value);
    }

    /**
     * Load additional locale file
     *
     * @param string $locale Locale code (e.g., 'fr', 'de')
     * @param string|null $filePath Custom file path (auto-detects if null)
     */
    public function loadLocale(string $locale, ?string $filePath = null): void
    {
        if (!$this->translator instanceof Translator) {
            return;
        }

        $file = $filePath ?? __DIR__ . "/../Locales/{$locale}.php";

        if (file_exists($file)) {
            $this->translator->addResource($file, $locale, 'validators');
        }
    }

    /**
     * Add translations directly without file (for custom validations)
     * 
     * @param array<string, string> $translations ['validation.key' => 'Message {field}']
     * @param string $locale
     */
    public function addTranslations(array $translations, string $locale = 'en'): void
    {
        if ($this->translator instanceof Translator) {
            $this->translator->addResource($translations, $locale, 'validators');
        }
    }
}