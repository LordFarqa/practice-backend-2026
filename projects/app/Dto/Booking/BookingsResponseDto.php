<?php
// app/Dto/Booking/BookingsResponseDto.php

namespace App\Dto\Booking;

use App\Models\BookingRooms;
use Illuminate\Database\Eloquent\Collection;

class BookingsResponseDto
{
    private Collection $bookings;
    
    public function __construct(Collection $bookings)
    {
        $this->bookings = $bookings;
    }
    
    public function toArray(): array
    {
        /** @var Collection<int, BookingRooms> $bookings */
        $bookings = $this->bookings;
        
        return $bookings->map(function (BookingRooms $booking): array {
            $dto = new BookingResponseDto($booking);
            return $dto->toArray();
        })->toArray();
    }
}