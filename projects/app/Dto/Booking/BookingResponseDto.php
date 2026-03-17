<?php
namespace App\Dto\Booking;

use App\Models\BookingRooms;

class BookingResponseDto
{
    private array $data;

    public function __construct(BookingRooms $booking)
    {
        $this->data = [
            'id' => $booking->id,
            'room_number' => $booking->room->number,
            'hotel_name' => $booking->room->hotel->name,
            'hotel_address' => $booking->room->hotel->address,
            'room_class' => $booking->room->room_classes->name,
            'booking_start' => $booking->booking_start,
            'booking_end' => $booking->booking_end,
            'status' => $booking->status->name,
            'user_name' => $booking->user->name . ' ' . $booking->user->surname
        ];
    }

    public function toArray(): array
    {
        return $this->data;
    }
}