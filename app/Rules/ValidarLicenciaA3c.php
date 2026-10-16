<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidarLicenciaA3c implements ValidationRule
{
    private const PATTERN = '/^Q\d{8}$/';

    public function __construct(private readonly mixed $documento = null)
    {
    }

    public function __invoke(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || preg_match(self::PATTERN, $value) !== 1) {
            $fail('El número ingresado no es válido para una licencia de categoría A-IIIc.');
            return;
        }

        if (!is_string($this->documento) || substr($value, 1) !== $this->documento) {
            $fail('Los 8 dígitos de la licencia deben coincidir exactamente con el DNI ingresado.');
        }
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $this($attribute, $value, $fail);
    }
}
