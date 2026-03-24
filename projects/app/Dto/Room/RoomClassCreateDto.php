<?php
// app/Dto/Room/RoomClassCreateDto.php

namespace App\Dto\Room;

class RoomClassCreateDto
{
    private string $name;
    private float $pricePerDay;

    public function __construct(array $data)
    {
        $this->name = $data['name'];
        $this->pricePerDay = $data['price_per_day'];
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'price_per_day' => $this->pricePerDay
        ];
    }
}