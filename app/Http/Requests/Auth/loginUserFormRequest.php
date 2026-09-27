<?php

namespace App\Http\Requests\Auth;

use App\DTO\Auth\LoginUserDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class loginUserFormRequest extends FormRequest
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
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'    => 'El correo electronico es obligatorio.',
            'email.email'       => 'El correo electronico debe tener un formato valido.',
            'password.required' => 'La contraseña es obligatoria.',
        ];
    }

    public function toDTO(): LoginUserDTO
    {
        return new LoginUserDTO(
            email: $this->input('email'),
            password: $this->input('password'),
        );
    }
}
