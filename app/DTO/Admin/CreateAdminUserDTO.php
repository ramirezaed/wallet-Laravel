<?php

namespace App\DTO\Admin;

use Illuminate\Http\UploadedFile;

class CreateAdminUserDTO
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public readonly string $nombre,
        public readonly string $email,
        public readonly string $password,
        public readonly int $edad,
        public readonly UploadedFile|string|null $imagen = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'nombre' => $this->nombre,
            'email' => $this->email,
            'password' => $this->password,
            'edad' => $this->edad,
            'imagen' => $this->imagen,
        ], fn ($value) => ! is_null($value) && $value !== '');
    }

    public static function fromArray(array $data): self
    {
        return new self(
            nombre: $data['nombre'],
            email: $data['email'],
            password: $data['password'],
            edad: $data['edad'],
            imagen: $data['imagen'] ?? null,
        );
    }
}
