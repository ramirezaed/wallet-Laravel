<?php

namespace App\Http\Requests\Admin;

use App\Models\Cuenta;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Cuenta|null $cuenta */
        $cuenta = $this->route('cuenta');

        return [
            'tipo' => ['sometimes', 'in:ahorro,corriente'],
            'moneda' => ['sometimes', 'in:ARS,USD'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.in' => 'El tipo de cuenta debe ser "ahorro" o "corriente".',
            'moneda.in' => 'La moneda debe ser "ARS" o "USD".',
        ];
    }
}
