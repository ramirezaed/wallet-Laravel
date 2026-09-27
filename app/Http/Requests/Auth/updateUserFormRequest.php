<?php

namespace App\Http\Requests\Auth;

use App\DTO\Auth\UpdateUserDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class updateUserFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth('api')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore(auth('api')->id()),
            ],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
            'edad' => ['sometimes', 'integer', 'between:18,120'],
            'imagen' => [
                'sometimes',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.string' => 'El nombre del usuario debe ser una cadena de texto.',
            'nombre.max' => 'El nombre del usuario no puede tener más de 255 caracteres.',
            'email.string' => 'El correo electrónico debe ser una cadena de texto.',
            'email.email' => 'El correo electrónico debe tener un formato válido.',
            'email.max' => 'El correo electrónico no debe exceder los 255 caracteres.',
            'email.unique' => 'El correo electrónico ya está en uso.',
            'password.string' => 'La contraseña debe ser una cadena de texto.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Debe ingresar nuevamente la misma contraseña.',
            'edad.integer' => 'La edad debe ser un número entero.',
            'edad.between' => 'La edad debe estar entre 18 y 120 años.',
            'imagen.file' => 'La imagen debe ser un archivo válido.',
            'imagen.image' => 'El archivo debe ser una imagen válida.',
            'imagen.uploaded' => 'No se pudo cargar la imagen. Verifica que no supere los 2 MB.',
            'imagen.max' => 'La imagen no puede superar los 2 MB.',
            'imagen.mimes' => 'La imagen debe estar en formato JPG, JPEG, PNG o WebP.',
        ];
    }

    public function toDTO(): UpdateUserDTO
    {
        $validated = $this->validated();

        return new UpdateUserDTO(
            nombre: $validated['nombre'] ?? null,
            email: $validated['email'] ?? null,
            password: $validated['password'] ?? null,
            edad: $validated['edad'] ?? null,
            imagen: $validated['imagen'] ?? null,
        );

    }
}
