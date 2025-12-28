<?php

namespace Gravity\Interfaces;

interface TranslatorInterface
{
    /**
     * Translates the given message.
     *
     * @param string $id The message id (e.g. 'validation.required')
     * @param array $parameters Placeholder replacements (e.g. ['{field}' => 'email'])
     * @param string|null $domain The translation domain (e.g. 'validators')
     * @param string|null $locale Override the current locale
     * @return string The translated string
     */
    public function trans(
        string $id,
        array $parameters = [],
        ?string $domain = null,
        ?string $locale = null
    ): string;

    /**
     * Get the current locale
     */
    public function getLocale(): string;

    /**
     * Set the current locale
     */
    public function setLocale(string $locale): void;
}