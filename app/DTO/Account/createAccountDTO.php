<?php

namespace App\DTO\Account;

class createAccountDTO
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public readonly int $usuario_id,
        //opcional el tipo de cuenta, por default se crea una centa de tipo "ahorro"
        public readonly ?string $tipo = null,
        //opcional el tipo de moneda, por default se crea con ars
        public readonly ?string $moneda = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'usuario_id' => $this->usuario_id,
            'tipo' => $this->tipo,
            'moneda' => $this->moneda,
        ], fn($value) => !is_null($value));
    }

    public static function fromArray(array $data): self
    {
        return new self(
            usuario_id: $data['usuario_id'],
            tipo: $data['tipo'] ?? null,
            moneda: $data['moneda'] ?? null,
        );
    }
}
