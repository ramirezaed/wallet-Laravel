<?php

namespace App\DTO\Admin;

class ListAdminAccountDTO
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public readonly string $orden = 'asc',
        public readonly int $perPage = 15,
    ) {}

    public function toArray(): array
    {
        return [
            'orden' => $this->orden,
            'per_page' => $this->perPage,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            orden: $data['orden'] ?? 'asc',
            perPage: $data['per_page'] ?? 15,
        );
    }
}
