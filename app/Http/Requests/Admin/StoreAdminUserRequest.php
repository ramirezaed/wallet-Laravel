<?php

namespace App\Http\Requests\Admin;

use App\DTO\Admin\CreateAdminUserDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAdminUserRequest extends FormRequest
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
     * El campo rol no se valida a proposito: el administrador solo puede crear
     * usuarios con rol "usuario", que fuerza CreateAdminUserService.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'edad' => ['required', 'integer', 'min:18', 'max:120'],
            'imagen' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.string' => 'El nombre debe ser texto.',
            'nombre.max' => 'El nombre no puede tener más de 255 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.string' => 'El correo electrónico debe ser texto.',
            'email.email' => 'El correo electrónico debe tener un formato válido.',
            'email.max' => 'El correo electrónico no debe exceder los 255 caracteres.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.string' => 'La contraseña debe ser texto.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'edad.required' => 'La edad es obligatoria.',
            'edad.integer' => 'La edad debe ser un número entero.',
            'edad.min' => 'El usuario debe tener al menos 18 años.',
            'edad.max' => 'La edad no puede ser mayor a 120 años.',
            'imagen.file' => 'La imagen debe ser un archivo válido.',
            'imagen.image' => 'El archivo debe ser una imagen válida.',
            'imagen.mimes' => 'La imagen debe estar en formato JPG, JPEG, PNG o WebP.',
            'imagen.max' => 'La imagen no puede superar los 2 MB.',
        ];
    }

    public function toDTO(): CreateAdminUserDTO
    {
        $validated = $this->validated();

        return new CreateAdminUserDTO(
            nombre: $validated['nombre'],
            email: $validated['email'],
            password: $validated['password'],
            edad: $validated['edad'],
            imagen: $validated['imagen'] ?? null,
        );
    }
}
