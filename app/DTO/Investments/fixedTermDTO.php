<?php

namespace App\DTO\Investments;

class fixedTermDTO
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public readonly float $monto,
        public readonly int $plazo,

    ) {}

    public function toArray(): array
    {
        return [
            'monto' => $this->monto,
            'tiempo' => $this->plazo,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            monto: $data['monto'],
            plazo: $data['plazo']
        );
    }
}
