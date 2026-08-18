<?php

namespace App\Services;

use InvalidArgumentException;

class PhoneNormalizer
{
    public function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) < 10 || strlen($digits) > 15) {
            throw new InvalidArgumentException('Informe um telefone internacional válido com DDI.');
        }

        return '+'.$digits;
    }
}
