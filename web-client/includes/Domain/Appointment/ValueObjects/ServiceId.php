<?php

declare(strict_types=1);

// Dossier: web-client/includes/Domain/Appointment/ValueObjects/

final class ServiceId
{
    private int $id;

    public function __construct(int $id)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID du service (ServiceId) doit être un entier positif. Reçu: " . $id);
        }
        $this->id = $id;
    }

    public function toInt(): int
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public static function fromInt(int $id): self
    {
        return new self($id);
    }
}
