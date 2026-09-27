<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cuenta_id' => ['sometimes', 'integer', 'exists:cuentas,id'],
            'tipo' => ['sometimes', 'in:deposito,transferencia_salida,transferencia_entrada'],
            'monto' => ['sometimes', 'numeric', 'gt:0'],
            'cbu_contraparte' => ['nullable', 'string', 'size:22'],
        ];
    }
}
