<?php

namespace Gravity\Translation;

use Gravity\Interfaces\TranslatorInterface;

class TranslationManager
{
    /**
     * Base default translator (EN loaded once per process).
     * Never mutate it after creation.
     */
    private static ?Translator $baseDefaultTranslator = null;

    /**
     * Instance translator (safe to mutate: locale, extra resources, etc.)
     */
    private ?TranslatorInterface $translator = null;

    /**
     * If false, we never auto-initialize the default translator
     * because a custom translator was provided.
     */
    private bool $useDefaultTranslations = true;

    /**
     * Constructor:
     * - Must initialize translator (tests expect it)
     * - Must not trigger repeated file I/O
     *
     * Strategy:
     * - Create base translator once (does file I/O once)
     * - Clone it per instance (cheap) so locale/resources don't leak across instances
     */
    public function __construct()
    {
        $this->translator = clone $this->getOrCreateBaseDefaultTranslator();
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
        $this->ensureTranslatorInitialized();

        if ($this->translator instanceof Translator) {
            $this->translator->setLocale($locale);
        }
    }

    /**
     * Get translated validation error message
     */
    public function getValidationMessage(
        string $testName,
        string $field,
        mixed $value,
        array $params = []
    ): string {
        $this->ensureTranslatorInitialized();

        if (!$this->translator) {
            return $this->getDefaultMessage($testName, $field);
        }

        $key = 'validation.' . $testName;

        $parameters = array_merge([
            'field' => $field,
            'value' => $this->formatValue($value),
        ], $params);

        return $this->translator->trans($key, $parameters, 'validators');
    }

    /**
     * Ensure translator exists.
     * - If using default translations and translator is null, clone base.
     * - If user set a custom translator, do nothing.
     */
    private function ensureTranslatorInitialized(): void
    {
        if ($this->translator === null && $this->useDefaultTranslations) {
            $this->translator = clone $this->getOrCreateBaseDefaultTranslator();
        }
    }

    /**
     * Create the base default translator once per process (includes file I/O once).
     * IMPORTANT: never mutate the returned instance (it's shared).
     */
    private function getOrCreateBaseDefaultTranslator(): Translator
    {
        if (self::$baseDefaultTranslator === null) {
            self::$baseDefaultTranslator = $this->createBaseDefaultTranslator();
        }

        return self::$baseDefaultTranslator;
    }

    /**
     * Build the base EN translator (one-time).
     */
    private function createBaseDefaultTranslator(): Translator
    {
        $translator = new Translator('en');
        $translator->setFallbackLocale('en');

        $enFile = __DIR__ . '/../Locales/en.php';
        if (!file_exists($enFile)) {
            throw new \RuntimeException("Default locale file not found: {$enFile}");
        }

        $translator->addResource($enFile, 'en', 'validators');

        return $translator;
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
        if (is_null($value)) return 'null';
        if (is_bool($value)) return $value ? 'true' : 'false';
        if ($value instanceof \DateTime) return $value->format('Y-m-d H:i:s');
        if (is_scalar($value)) return (string) $value;
        if (is_array($value)) return 'array';
        if (is_object($value)) return 'object';
        return gettype($value);
    }

    /**
     * Load additional locale file
     */
    public function loadLocale(string $locale, ?string $filePath = null): void
    {
        $this->ensureTranslatorInitialized();

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
     */
    public function addTranslations(array $translations, string $locale = 'en'): void
    {
        $this->ensureTranslatorInitialized();

        if ($this->translator instanceof Translator) {
            $this->translator->addResource($translations, $locale, 'validators');
        }
    }

    /**
     * Reset the static base translator cache (mainly for testing).
     * Also clears instance translator if you want a fully clean state in tests.
     */
    public static function resetCache(): void
    {
        self::$baseDefaultTranslator = null;
    }
}
