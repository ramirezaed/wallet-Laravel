<?php

namespace App\DTO\Auth;

class RegisterUserDTO
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public readonly string $nombre,
        public readonly string $email,
        public readonly string $password,
        public readonly int $edad,
    ) {}

    public function toArray(): array
    {
        return [
            'nombre'        => $this->nombre,
            'email' => $this->email,
            'password'       => $this->password,
            'edad'       => $this->edad,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            nombre: $data["nombre"],
            email: $data["email"],
            password: $data["password"],
            edad: $data["edad"],
        );
    }
}
