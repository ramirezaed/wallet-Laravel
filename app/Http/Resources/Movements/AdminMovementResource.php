<?php

namespace App\Http\Resources\Movements;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'monto' => number_format((float) $this->monto, 2, '.', ''),
            'cbu_contraparte' => $this->cbu_contraparte,
            'fecha' => $this->created_at?->toISOString(),

            'cuenta' => [
                'id' => $this->cuenta?->id,
                'cbu' => $this->cuenta?->cbu,
                'tipo' => $this->cuenta?->tipo,
                'moneda' => $this->cuenta?->moneda,
                'usuario_id' => $this->cuenta?->usuario_id,
            ],
        ];
    }
}
