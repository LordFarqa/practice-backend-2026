<?php
// app/Dto/Booking/BookingResponseDto.php

namespace App\Dto\Booking;

use App\Models\BookingRooms;
use App\Models\Room;
use App\Models\Hotel;

class BookingResponseDto
{
    private BookingRooms $booking;
    
    public function __construct(BookingRooms $booking)
    {
        $this->booking = $booking;
    }
    
    public function toArray(): array
    {
        // Безопасное получение данных
        $room = $this->booking->room;
        $hotel = $room ? $room->hotel : null;
        
        $hotelName = '';
        if ($hotel && is_string($hotel->name)) {
            $hotelName = $hotel->name;
        } elseif ($hotel && method_exists($hotel, 'getName')) {
            $hotelName = (string) $hotel->getName();
        }
        
        return [
            'id' => $this->booking->id,
            'room_id' => $this->booking->room_id,
            'room_number' => $room ? (string) $room->number : '',
            'hotel_name' => $hotelName,
            'check_in' => $this->booking->check_in ?? $this->booking->check_in_date ?? null,
            'check_out' => $this->booking->check_out ?? $this->booking->check_out_date ?? null,
            'status' => $this->booking->status ?? $this->booking->status_id ?? 'pending',
            'total_price' => (float) ($this->booking->total_price ?? $this->booking->total_amount ?? 0),
            'created_at' => $this->booking->created_at?->format('Y-m-d H:i:s')
        ];
    }
}