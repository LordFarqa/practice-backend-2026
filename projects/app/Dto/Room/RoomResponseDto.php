<?php
// app/Dto/Room/RoomResponseDto.php

namespace App\Dto\Room;

use App\Models\Room;

class RoomResponseDto
{
    public function __construct(private readonly Room $room) {}

    public function toArray(): array
    {
        return [
            'id' => $this->room->id,
            'number' => $this->room->number,
            'hotel_id' => $this->room->hotel_id,
            'hotel_name' => $this->room->hotel->name ?? null,
            'class_id' => $this->room->class_id,
            'class_name' => $this->room->room_classes->name ?? null,
            'price_per_day' => $this->room->room_classes->price_per_day ?? null,
            'floor' => $this->room->floor
        ];
    }
}