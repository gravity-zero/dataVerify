<?php

require __DIR__ . '/../vendor/autoload.php';

use Gravity\DataVerify;
use Gravity\Validations\ValidationStrategy;

class SiretStrategy extends ValidationStrategy
{
    public function getName(): string
    {
        return 'siret';
    }
    
    protected function handler(mixed $value): bool
    {
        if (!is_string($value) || strlen($value) !== 14) {
            return false;
        }
        
        return ctype_digit($value);
    }
}

$data = new stdClass();
$data->company_siret = '12345678901234';

$dv = new DataVerify($data);
$dv->registerStrategy(new SiretStrategy());
$dv->field('company_siret')->required->siret;

echo $dv->verify() ? "✓ SIRET valide\n" : "✗ SIRET invalide\n";