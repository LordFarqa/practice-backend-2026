<?php


namespace App\Dto\Room;

class RoomCreateDto
{
    public function __construct(
        private readonly int $hotelId,
        private readonly array $data
    ) {}

    public function getHotelId(): int
    {
        return $this->hotelId;
    }

    public function toArray(): array
    {
        return [
            'hotel_id' => $this->hotelId,
            'number' => $this->data['number'],
            'class_id' => $this->data['class_id'],
            'floor' => $this->data['floor']
        ];
    }
}