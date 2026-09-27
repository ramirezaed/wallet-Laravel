<?php

namespace App\Http\Requests\Admin;

use App\DTO\Admin\ListAdminAccountDTO;
use Illuminate\Foundation\Http\FormRequest;

class ListAdminAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'orden' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'orden.in' => 'El valor del parámetro "orden" debe ser "asc" o "desc".',
            'per_page.integer' => 'El valor del parámetro "per_page" debe ser un número entero.',
            'per_page.min' => 'El valor del parámetro "per_page" debe ser al menos 1.',
            'per_page.max' => 'El valor del parámetro "per_page" no puede ser mayor a 100.',
        ];
    }

    public function toDTO(): ListAdminAccountDTO
    {
        $validated = $this->validated();

        return new ListAdminAccountDTO(
            orden: $validated['orden'] ?? 'asc',
            perPage: $validated['per_page'] ?? 15,
        );
    }
}
