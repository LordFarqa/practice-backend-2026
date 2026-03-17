<?php
namespace App\Dto\Booking;

use Illuminate\Database\Eloquent\Collection;

class BookingsResponseDto
{
    private array $bookings = [];

    public function __construct(Collection $bookings)
    {
        foreach ($bookings as $booking) {
            $this->bookings[] = (new BookingResponseDto($booking))->toArray();
        }
    }

    public function toArray(): array
    {
        return $this->bookings;
    }
}