<?php

namespace App\Http\Resources\investments;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class fixedTermResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'Fecha Inicio'       => $this->resource['created_at'],
            'Fecha Fin'     => $this->resource['fecha_fin'],
            'Monto Invertido' => $this->resource['monto'],
            'Interes ganado'  => $this->resource['interes'],
            'Total'           => $this->resource['total'],

        ];
    }
}
