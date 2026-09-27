<?php

namespace App\Http\Requests\Admin;

use App\DTO\Account\createAccountDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Clase usada para crear cuenta desde administrador para usuario sin cuenta
class StoreAdminAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'usuario_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
                Rule::unique('cuentas', 'usuario_id')->whereNull('deleted_at'),
            ],
            'tipo' => ['nullable', 'in:ahorro,corriente'],
            'moneda' => ['nullable', 'in:ARS,USD'],
        ];
    }

    public function messages(): array
    {
        return [
            'usuario_id.required' => 'Debe ingresar el id del usuario',
            'usuario_id.exists' => 'El usuario no existe o está dado de baja.',
            'usuario_id.unique' => 'El usuario ya tiene una cuenta.',
            'tipo.in' => 'El tipo de cuenta debe ser "ahorro" o "corriente".',
            'moneda.in' => 'La moneda debe ser "ARS" o "USD".',
        ];
    }

    public function toDTO(): createAccountDTO
    {
        $validated = $this->validated();

        return new createAccountDTO(
            usuario_id: $validated['usuario_id'],
            tipo: $validated['tipo'] ?? null,
            moneda: $validated['moneda'] ?? null,
        );
    }
}
