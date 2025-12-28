<?php
namespace Gravity\Validations\File;

use Gravity\Validations\ValidationStrategy;
use Gravity\Attributes\{ValidationRule, Param};

#[ValidationRule(
    name: 'fileExists',
    description: 'Validates that a file exists at the given path',
    category: 'File',
    examples: ['$verifier->field("test")->fileExists']
)]
class FileExistsValidation extends ValidationStrategy
{
    public function getName(): string
    {
        return 'fileExists';
    }

    protected function handler(
        mixed $value
    ): bool {
        return file_exists($value) && is_file($value);
    }
}
