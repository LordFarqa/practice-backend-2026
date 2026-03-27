<?php
// app/Dto/Room/RoomUpdateDto.php

namespace App\Dto\Room;

class RoomUpdateDto
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
            'number' => $this->data['number'] ?? null,
            'hotel_id' => $this->data['hotel_id'] ?? null,
            'class_id' => $this->data['class_id'] ?? null,
            'floor' => $this->data['floor'] ?? null
        ], fn($value) => !is_null($value));
    }
}