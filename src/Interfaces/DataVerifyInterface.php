<?php

namespace Gravity\Interfaces;

interface DataVerifyInterface
{
    public function verify(): bool;

    public function getErrors(bool $asObject = false): array|bool;

    public function field(string $name): self;

    public function subfield(string $name): self;

    public function alias(string $name): self;

    public function errorMessage(string $message): self;

    public function registerStrategy(ValidationStrategyInterface $strategy): self;
}