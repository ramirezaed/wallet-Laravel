<?php

namespace App\Generators;

use App\Models\Cuenta;

class CbuGenerator
{
    public static function generate(): string
    {
        do {
            $cbu = '0000009';

            for ($i = 0; $i < 15; $i++) {
                $cbu .= random_int(0, 9);
            }
        } while (Cuenta::where('cbu', $cbu)->exists());

        return $cbu;
    }
}
