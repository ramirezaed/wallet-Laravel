<?php

namespace App\Http\Requests\Investments;

use App\DTO\Investments\fixedTermDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class fixedTerRequest extends FormRequest
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
            "monto" => "required|integer|min:1",
            "plazo" => "required|integer|min:30|max:365"
        ];
    }


    public function messages(): array
    {
        return [
            'monto.required' => 'El monto es obligatorio.',
            'monto.integer'  => 'El monto debe ser un número entero.',
            'monto.min'      => 'El monto debe ser mayor a 0.',

            'plazo.required' => 'El plazo es obligatorio.',
            'plazo.integer'  => 'El plazo debe ser un número entero.',
            'plazo.min'      => 'El plazo mínimo es de 30 días.',
            'plazo.max'      => 'El plazo máximo es de 365 días.',
        ];
    }

    public function toDTO(): fixedTermDTO
    {
        return new fixedTermDTO(
            monto: $this->input("monto"),
            plazo: $this->input("plazo"),
        );
    }
}
