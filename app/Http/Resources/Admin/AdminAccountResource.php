<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminAccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'usuario_id' => $this->usuario_id,
            'cbu' => $this->cbu,
            'saldo' => number_format((float) $this->saldo, 2, '.', ''),
            'tipo' => $this->tipo,
            'moneda' => $this->moneda,
            'nombre_usuario' => $this->usuario?->nombre,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
