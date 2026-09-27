<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        //Retornamos el id, nombre y email del usuario autenticado
        //Se agrega el campo edad y la imagen del usuario autenticado
        /*
        Como internamente el proyecto utiliza el campo nombre,
        el Resource lo transforma a name para respetar el formato solicitado por la API:
        'name' => $this->nombre,
        */
        return [
        'id' => $this->id,
        'nombre' => $this->nombre,
        'email' => $this->email,
        'edad' => $this->edad,
        'imagen' => $this->imagen,
    ];
    }
}