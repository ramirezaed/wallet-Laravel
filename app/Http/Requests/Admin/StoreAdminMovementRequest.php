<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdminMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cuenta_id' => ['required', 'integer', 'exists:cuentas,id'],
            'tipo' => ['required', 'in:deposito,transferencia_salida,transferencia_entrada'],
            'monto' => ['required', 'numeric', 'gt:0'],
            'cbu_contraparte' => ['nullable', 'string', 'size:22'],
        ];
    }
}
