<?php

namespace App\Http\Requests\Admin;

use App\DTO\Admin\UpdateAdminUserDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminUserRequest extends FormRequest
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
     * El campo rol no se puede modificar desde este endpoint.
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
                // Ignora al usuario objetivo de la ruta, no al administrador que ejecuta.
                Rule::unique('users', 'email')->ignore($this->route('user')?->id),
            ],
            'password' => ['sometimes', 'string', 'min:8'],
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
            'nombre.string' => 'El nombre debe ser texto.',
            'nombre.max' => 'El nombre no puede tener más de 255 caracteres.',
            'email.string' => 'El correo electrónico debe ser texto.',
            'email.email' => 'El correo electrónico debe tener un formato válido.',
            'email.max' => 'El correo electrónico no debe exceder los 255 caracteres.',
            'email.unique' => 'El correo electrónico ya está en uso.',
            'password.string' => 'La contraseña debe ser texto.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'edad.integer' => 'La edad debe ser un número entero.',
            'edad.between' => 'La edad debe estar entre 18 y 120 años.',
            'imagen.file' => 'La imagen debe ser un archivo válido.',
            'imagen.image' => 'El archivo debe ser una imagen válida.',
            'imagen.mimes' => 'La imagen debe estar en formato JPG, JPEG, PNG o WebP.',
            'imagen.max' => 'La imagen no puede superar los 2 MB.',
        ];
    }

    public function toDTO(): UpdateAdminUserDTO
    {
        $validated = $this->validated();

        return new UpdateAdminUserDTO(
            nombre: $validated['nombre'] ?? null,
            email: $validated['email'] ?? null,
            password: $validated['password'] ?? null,
            edad: $validated['edad'] ?? null,
            imagen: $validated['imagen'] ?? null,
        );
    }
}
