<?php

namespace Gravity\Interfaces;

interface ValidationHandlerInterface
{
    public function addValidation(string $testName, array $arguments = []): void;
    public function getValidations(): array;
    public function getAlias(): ?string;
    public function setAlias(string $alias): void;
    public function getErrorMessage(): ?string;
    public function setErrorMessage(string $message): void;
    public function addConditionalValidation(string $testName, array $args, array $condition): void;
    public function getConditionalValidations(): array;
}