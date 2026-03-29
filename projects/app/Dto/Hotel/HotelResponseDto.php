<?php
// app/Dto/Hotel/HotelResponseDto.php

namespace App\Dto\Hotel;

use App\Models\Hotel;

class HotelResponseDto
{
    public function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly string $address,
        private readonly string $class
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'class' => $this->class
        ];
    }
    
    public static function fromModel(Hotel $hotel): self
    {
        return new self(
            $hotel->id,
            $hotel->name,
            $hotel->address,
            $hotel->class
        );
    }
}