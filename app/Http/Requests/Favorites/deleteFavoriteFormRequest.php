<?php

namespace App\Http\Requests\Favorites;

use App\DTO\Favorites\deleteFavoriteDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class deleteFavoriteFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * El CBU y el idUser llegan como parametros de la URL, no en el body.
     * Los incorporamos a los datos a validar para que rules() pueda evaluarlos.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'cbu' => $this->route('cbu'),
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
            'cbu' => ['required', 'string', 'digits:22'],
            'idUser' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'cbu.required' => 'El CBU es obligatorio.',
            'cbu.digits' => 'El CBU debe tener 22 digitos numericos.',
            'idUser.required' => 'El id de usuario es obligatorio.',
            'idUser.integer' => 'El id de usuario debe ser un numero entero.',
        ];
    }

    public function toDTO(): deleteFavoriteDTO
    {
        return new deleteFavoriteDTO(
            cbu: $this->input('cbu'),
            idUser: (int) $this->input('idUser'),
        );
    }
}
