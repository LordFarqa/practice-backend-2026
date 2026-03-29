<?php
// app/Dto/Hotel/UpdateHotelDto.php

namespace App\Dto\Hotel;

class UpdateHotelDto
{
    public function __construct(
        private readonly int $id,
        private readonly array $data
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->data['name'] ?? null,
            'address' => $this->data['address'] ?? null,
            'class' => $this->data['class'] ?? null
        ], fn($value) => $value !== null);
    }
}