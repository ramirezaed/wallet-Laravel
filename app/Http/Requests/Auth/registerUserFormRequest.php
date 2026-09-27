<?php

namespace App\Http\Requests\Auth;

use App\DTO\Auth\RegisterUserDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class registerUserFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => 'required|string',
            'email' => [
                'required',
                'string',
                'email',
                Rule::unique('users', 'email')->where(
                    fn($query) => $query->where('eliminado', false)
                ),
            ],
            'password' => 'required|string|confirmed',
            'edad' => 'required|integer|min:18',
        ];
    }

    public function messages(): array
    {
        return [

            // Campos requeridos
            'nombre.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electronico es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
            'edad.required' => 'La edad es obligatoria.',

            // Tipos de datos
            'edad.integer' => 'La edad debe ser un número entero.',
            // Formato específico
            'email.email' => 'El correo electrónico debe tener un formato válido.',
            // Unicidad
            'email.unique' => 'Este correo electronico ya esta registrado.',
            // Longitud/valor mínimo
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'edad.min' => 'Debes tener al menos 18 años para registrarte.',
            // Opcional: si quieres agregar mensajes para string (aunque no están en tus reglas)
            'nombre.string' => 'El nombre debe ser texto.',
            'email.string' => 'El correo electrónico debe ser texto.',
            'password.string' => 'La contraseña debe ser texto.',
        ];
    }

    public function toDTO(): RegisterUserDTO
    {
        return new RegisterUserDTO(
            nombre: $this->input('nombre'),
            email: $this->input('email'),
            password: $this->input('password'),
            edad: $this->input('edad')
        );
    }
}
