<?php

namespace Gravity\Interfaces;

interface ValidationErrorInterface
{
    public function getField(): string;

    public function getAlias(): ?string;

    public function getTest(): string;

    public function getMessage(): string;

    public function getValue(): mixed;

    public function toObject(): object;

    public function toArray(): array;
}