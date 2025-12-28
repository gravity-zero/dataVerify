<?php

namespace Gravity\Translation;

use Gravity\Interfaces\TranslatorInterface;

class Translator implements TranslatorInterface
{
    private string $locale;
    private string $fallbackLocale = 'en';
    private array $messages = [];
    private LoaderFactory $loaderFactory;

    public function __construct(string $locale = 'en', ?LoaderFactory $loaderFactory = null)
    {
        $this->locale = $locale;
        $this->loaderFactory = $loaderFactory ?? LoaderFactory::createDefault();

        if (!$this->loaderFactory) {
            throw new \LogicException("LoaderFactory cannot be null");
        }
    }

    /**
     * Add a translation resource (auto-detects loader via factory)
     */
    public function addResource(array|string $resource, string $locale, string $domain = 'messages'): void
    {
        $loader = $this->loaderFactory->getLoader($resource);
        $translations = $loader->load($resource, $locale, $domain);
        
        if (!isset($this->messages[$locale])) {
            $this->messages[$locale] = [];
        }
        if (!isset($this->messages[$locale][$domain])) {
            $this->messages[$locale][$domain] = [];
        }

        $previousCount = count($this->messages[$locale][$domain]);

        $this->messages[$locale][$domain] = array_merge(
            $this->messages[$locale][$domain],
            $translations
        );

        $newCount = count($this->messages[$locale][$domain]);
        if ($newCount < $previousCount) {
            throw new \LogicException("Translations merge resulted in data loss");
        }
    }

    public function trans(
        string $id,
        array $parameters = [],
        ?string $domain = null,
        ?string $locale = null
    ): string {
        $locale = $locale ?? $this->locale;
        $domain = $domain ?? 'messages';

        $message = $this->getMessage($id, $locale, $domain);
        
        if ($message === null && $locale !== $this->fallbackLocale) {
            $message = $this->getMessage($id, $this->fallbackLocale, $domain);
        }

        if ($message === null) {
            return $id;
        }

        return $this->replacePlaceholders($message, $parameters);
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function setFallbackLocale(string $locale): void
    {
        $this->fallbackLocale = $locale;
    }

    private function getMessage(string $id, string $locale, string $domain): ?string
    {
        return $this->messages[$locale][$domain][$id] ?? null;
    }

    protected function replacePlaceholders(string $message, array $parameters): string
    {
        foreach ($parameters as $key => $value) {
            $placeholder = str_starts_with($key, '{') ? $key : "{{$key}}";
            
            if ($value instanceof \DateTime) {
                $stringValue = $value->format('Y-m-d');
            } elseif (is_scalar($value)) {
                $stringValue = (string)$value;
            } elseif (is_null($value)) {
                $stringValue = 'null';
            } elseif (is_bool($value)) {
                $stringValue = $value ? 'true' : 'false';
            } elseif (is_array($value)) {
                $stringValue = 'array';
            } elseif (is_object($value)) {
                $stringValue = 'object';
            } else {
                $stringValue = gettype($value);
            }
            
            $message = str_replace($placeholder, $stringValue, $message);
        }
        return $message;
    }
}