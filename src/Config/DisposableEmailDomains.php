<?php

namespace Gravity\Config;

/**
 * Configuration for disposable email domains
 * 
 * Used by DisposableEmailValidation to check against known temporary email providers
 */
class DisposableEmailDomains
{
    /**
     * List of known disposable email domain patterns
     * 
     * @var array<string>
     */
    public const DEFAULTS = [
        "@yopmail",
        "@ymail",
        "@jetable",
        "@trashmail",
        "@jvlicenses",
        "@temp-mail",
        "@emailnax",
        "@datakop"
    ];
}
