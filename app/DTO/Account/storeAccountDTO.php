<?php

namespace App\DTO\Account;

class storeAccountDTO
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public float $amount
    ) {}

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            amount: $data['amount'],
        );
    }
}
