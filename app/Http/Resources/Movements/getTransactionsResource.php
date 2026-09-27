<?php

namespace App\Http\Resources\Movements;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class getTransactionsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "tipo" => $this->tipo,
            "monto" => $this->monto,
            "cbu_contraparte" => $this->cbu_contraparte,
            "created_at" => $this->created_at
        ];
    }
}
