<?php

namespace App\DTO\Favorites;

class listFavoritesDTO
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public int $idUser,
    ) {}

    public function toArray(): array
    {
        return [
            'idUser' => $this->idUser,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            idUser: $data['idUser'],
        );
    }
}
