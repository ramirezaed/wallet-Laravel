<?php

namespace App\DTO\Favorites;

class deleteFavoriteDTO
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public string $cbu,
        public int $idUser,
    ) {}

    public function toArray(): array
    {
        return [
            'cbu' => $this->cbu,
            'idUser' => $this->idUser,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            cbu: $data['cbu'],
            idUser: $data['idUser'],
        );
    }
}
