<?php
namespace Gravity\Validations\File;

use Gravity\Validations\ValidationStrategy;
use Gravity\Attributes\{ValidationRule, Param};

#[ValidationRule(
    name: 'fileMime',
    description: 'Validates that a file has an allowed MIME type',
    category: 'File',
    examples: ['$verifier->field("test")->fileMime("image/jpeg")']
)]
class FileMimeValidation extends ValidationStrategy
{
    public function getName(): string
    {
        return 'fileMime';
    }

    protected function handler(
        mixed $value,
        #[Param('Allowed MIME type(s)', example: 'image/jpeg')]
        string|array $mime
    ): bool {
        if (!file_exists($value)) {
            return false;
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->file($value);
        if (is_array($mime)) {
            return in_array($detectedMime, $mime, true);
        }
        return $detectedMime === $mime;
    }
}
