<?php
// app/Dto/Room/RoomClassUpdateDto.php

namespace App\Dto\Room;

class RoomClassUpdateDto
{
    private int $id;
    private ?string $name;
    private ?float $pricePerDay;

    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->name = $data['name'] ?? null;
        $this->pricePerDay = $data['price_per_day'] ?? null;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'price_per_day' => $this->pricePerDay
        ]);
    }
}