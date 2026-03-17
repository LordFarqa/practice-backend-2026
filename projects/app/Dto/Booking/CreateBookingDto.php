<?php
namespace App\Dto\Booking;

class CreateBookingDto
{
    private readonly int $room_id;
    private readonly string $booking_start;
    private readonly string $booking_end;
    private readonly int $user_id;
    private readonly int $status_id;

    public function __construct(array $data)
    {
        $this->room_id = $data['room_id'];
        $this->booking_start = $data['booking_start'];
        $this->booking_end = $data['booking_end'];
        $this->user_id = $data['user_id'];
        $this->status_id = $data['status_id'] ?? 1; // По умолчанию активное бронирование
    }

    public function toArray(): array
    {
        return [
            'room_id' => $this->room_id,
            'booking_start' => $this->booking_start,
            'booking_end' => $this->booking_end,
            'user_id' => $this->user_id,
            'status_id' => $this->status_id
        ];
    }
}