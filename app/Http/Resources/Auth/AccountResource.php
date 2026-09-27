<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        //Retornamos el CBU y el saldo formateado a dos decimales como un string
        //Agregamos el tipo de cuenta y la moneda a la respuesta
        return [
            'cbu' => $this->cbu,
            'saldo' => number_format((float) $this->saldo, 2, '.', ''),
            'tipo' => $this->tipo,
            'moneda' => $this->moneda,
        ];
    }
}
