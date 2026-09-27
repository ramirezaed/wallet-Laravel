<?php

namespace App\Http\Requests\Favorites;

use App\DTO\Favorites\listFavoritesDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class listFavoritesFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * El idUser llega como parametro de la URL, no en el body.
     * Lo incorporamos a los datos a validar para que rules() pueda evaluarlo.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'idUser' => $this->route('idUser'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'idUser' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'idUser.required' => 'El id de usuario es obligatorio.',
            'idUser.integer' => 'El id de usuario debe ser un numero entero.',
        ];
    }

    public function toDTO(): listFavoritesDTO
    {
        return new listFavoritesDTO(
            idUser: (int) $this->input('idUser'),
        );
    }
}
